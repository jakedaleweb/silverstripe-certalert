<?php

class CertAlertTask extends BuildTask
{
    protected $description = "Alerts your certs - checks given paths and emails given recipients if any certificates are about to expire";
    public function run($request)
    {
        $certAlert = new CertAlert();
        $messages = $certAlert->checkCerts();
        foreach ($messages as $message) {
            $this->log($message, SS_Log::NOTICE);
        }
    }

    /**
    * Outputs feedback to the terminal or browser, logs the same thing.
    *
    * @param mixed $message
    * @param mixed $errorType
    */
    private function log($message, $errorType = SS_Log::ERR)
    {
        SS_Log::log($message, $errorType);
        echo '<p>'.$message.'</p>';
    }
}
