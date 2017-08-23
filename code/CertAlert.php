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
    private function get_cert_paths()
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
    private function get_alert_time()
    {
        $time = Config::inst()->get('CertAlert', 'alert_time');
        $timeUnit = explode(' ', $time);
        if (!in_array($timeUnit[1], self::$allowed_time_units)) {
            throw new Exception("Invalid unit of time for alert_time, see the README for valid units");
        }
        return $time;
    }

    /**
    * @return Array
    */
    private function get_alert_addresses()
    {
        $addresses = Config::inst()->get('CertAlert', 'alert_addresses');
        if (!$addresses) {
            throw new Exception("No alert_addresses supplied");
        }
        return $addresses;
    }

    /**
    * Sends the email alert
    *
    * @param Array $certinfo | String $alertTime
    * @return Email $sent
    */
    private function email_alert($certPath, $certInfo, $alertTime)
    {
        $config = SiteConfig::current_site_config();
        $emailContent = $config->CertAlertText;
        $sendTo = self::get_alert_addresses();

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
            $sent = $email->send();
        }
    }

    /**
    * Checks supplied certificate paths and emails alerts for each if expiring within alert_time
    * @return Array $message
    */
    public function check_cert_dirs()
    {
        $alertTime = $this->get_alert_time();
        $allowedExtensions = Config::inst()->get('CertAlert', 'allowed_certificate_extensions');

        // The time to compare with the expiration time of each certificate - as a UNIX timestamp
        $nowPlusAlertTime = strtotime($alertTime);

        $certPaths = $this->get_cert_paths();
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

        if (!$certs) {
            return false;
        }

        $message = [];
        foreach ($certs as $cert) {
            $certinfo = openssl_x509_parse(file_get_contents($cert));
            $niceTime = date("m/d/Y", $certinfo['validTo_time_t']);
            if ($certinfo['validTo_time_t'] < $nowPlusAlertTime) {
                $this->email_alert($cert, $certinfo, $alertTime);
                $message[] = "$cert is expiring on $niceTime, cert alerted";
                continue;
            }
            $message[] = "$cert is valid until $niceTime";
        }
        return $message;
    }
}
