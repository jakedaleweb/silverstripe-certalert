<?php

class CertAlertTask extends BuildTask
{
    public function run($request)
    {
        $certAlert = new CertAlert();
        $messages = $certAlert->check_cert_dirs();
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
