<?php

class CertAlert extends \Object
{
    /**
    * Path(s) to directories containing certificates to check
    * @var array
    * @config
    */
    private static $cert_paths = [];

    /**
    * The time between the expiry of a given certificate and alerting
    * unit  (('sec' | 'second' | 'min' | 'minute' | 'hour' | 'day' | 'fortnight' | 'forthnight' | 'month' | 'year') 's'?)) | 'weeks' | daytext)
    * @var String
    * @config
    */
    private static $alert_time = "4 weeks";

    /**
    * Email addresses to receive the alerts
    * @var Array
    * @config
    */
    private static $alert_addresses = [];

    /**
    * Allowed units of time
    * @var Array
    */
    private static $allowed_time_units = [
        'sec',
        'second',
        'min',
        'minute',
        'hour',
        'day',
        'fortnight',
        'forthnight',
        'month',
        'year',
        'secs',
        'seconds',
        'mins',
        'minutes',
        'hours',
        'days',
        'fortnights',
        'forthnights',
        'months',
        'years',
        'weeks',
        'daytext'
    ];

    /**
    * Allowed extensions of certificates
    * @var Array
    */
    private static $allowed_certificate_extensions = [
        'pem',
        'cer',
        'crt',
        'der',
        'p7b',
        'p7c',
        'p12',
        'pfx',
    ];

    /**
    * @return Array
    */
    private function getCertPaths()
    {
        $paths = Config::inst()->get('CertAlert', 'cert_paths');
        if (!$paths) {
            throw new Exception("No cert_paths supplied");
        }
        $formattedPaths = [];
        foreach ($paths as $path) {
            if (substr($path, -1) !== '/') {
                $formattedPaths[] = "$path/";
            } else {
                $formattedPaths[] = $path;
            }
        }
        return $formattedPaths;
    }

    /**
    * @return String
    */
    private function getAlertTime()
    {
        $time = Config::inst()->get('CertAlert', 'alert_time');
        $timeUnit = explode(' ', $time);
        if (!in_array($timeUnit[1], self::$allowed_time_units)) {
            return false;
        }
        return $time;
    }

    /**
    * Return certificates that will be checked at the supplied cert_paths
    * @param Array $certPaths
    * @return Array
    */
    public function getCertsToCheck()
    {
        $allowedExtensions = Config::inst()->get('CertAlert', 'allowed_certificate_extensions');
        $certPaths = $this->getCertPaths();
        $certs = [];
        foreach ($certPaths as $certPath) {
            $filesInDir = scandir($certPath);
            foreach ($filesInDir as $file) {
                if (strpos($file, '.')) {
                    $explode = explode('.', $file);
                    $extension = $explode[1];
                    if (in_array($extension, $allowedExtensions)) {
                        $certs[] = "$certPath$file";
                    }
                }

            }
        }
        return $certs;
    }

    /**
    * Sends the email alert
    *
    * @param String $certPath, Array $certinfo, String $alertTime, Array $sendTo
    * @return Email $sent | String
    */
    private function emailAlert($certPath, $certInfo, $alertTime, $sendTo)
    {
        $config = SiteConfig::current_site_config();
        $emailContent = $config->CertAlertText;

        foreach ($sendTo as $to) {
            $email = Email::create();
            $email->setTo($to);
            $email->setFrom('platform@silverstripe.com');
            $email->setSubject(sprintf('Certificate for %s is about to expire', $certInfo['subject']['CN']));
            if ($emailContent) {
                $email->setBody(sprintf("Your certificate loaded at %s covering the domain %s is about to expire in %s <br> $emailContent", $certPath, $certInfo['subject']['CN'], $certInfo['validTo_time_t']));
            } else {
                $email->setBody(sprintf('Your certificate loaded at %s is about to expire in %s', $certPath, $certInfo['subject']['CN'], $certInfo['validTo_time_t']));
            }
            $email->send();
        }
    }

    /**
    * Checks supplied certificate paths and emails alerts for each if expiring within alert_time
    * @return Array $message
    */
    public function checkCerts()
    {
        $message = [];

        $alertTime = $this->getAlertTime();
        if (!$alertTime) {
            $message[] = "Alert time incorectly configured, see README for valid units.";
        }

        $nowPlusAlertTime = strtotime($alertTime);
        $certs = $this->getCertsToCheck();
        if (!$certs) {
            $message[] = "No certs to check, you may want to see if there are certificates with the allowed extensions, as documented in the README, in the destination directories.";
        }

        $sendTo = Config::inst()->get('CertAlert', 'alert_addresses');
        if (!$sendTo) {
            $message[] = "No alert_addresses configured to send alerts to";
        }

        if ($message) {
            return $message;
        }

        foreach ($certs as $cert) {
            $certinfo = openssl_x509_parse(file_get_contents($cert));
            $niceTime = date("m/d/Y", $certinfo['validTo_time_t']);
            if ($certinfo['validTo_time_t'] < $nowPlusAlertTime) {
                $this->emailAlert($cert, $certinfo, $alertTime, $sendTo);
                $message[] = "$cert is expiring on $niceTime, cert alerted";
                continue;
            }
            $message[] = "$cert is valid until $niceTime";
        }
        return $message;
    }
}
