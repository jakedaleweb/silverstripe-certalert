<?php

class CertAlertTest extends SapphireTest
{

    private static $cert_paths = [
        '/tmp/',
        '/tmp/test/',
    ];


    private static $test_extensions = [
        'pem',
        'cer',
        'crt',
        'der',
        'p7b',
        'p7c',
        'p12',
        'pfx',
    ];

    protected $usesDatabase = true;

    /**
    * Sets up mock certificates for the testing and configures config for CertAlert set-up
    */
    public function setUp()
    {
        parent::setUp();

        $addresses = [
            'test@kjnkjn.com',
            'test2@kjnkjn.com',
        ];

        Config::inst()->update('CertAlert', 'cert_paths', self::$cert_paths);
        Config::inst()->update('CertAlert', 'alert_time', "8 weeks");
        Config::inst()->update('CertAlert', 'alert_addresses', $addresses);

        // Assumes tests are being run from parent projects
        $content = file_get_contents("silverstripe-certalert/tests/test.crt");

        foreach (self::$cert_paths as $certPath) {
            if (!file_exists($certPath)) {
                mkdir($certPath, 0777, true);
            }
            foreach (self::$test_extensions as $extension) {
                $myfile = fopen("$certPath"."crt".".$extension", "w");
                fwrite($myfile, $content);
            }
        }
    }

    /**
    * Remove mock certificates
    */
    public function tearDown()
    {
        parent::tearDown();
        // Delete mock certificates
        foreach (self::$cert_paths as $certPath) {
            foreach (self::$test_extensions as $extension) {
                unlink("$certPath"."crt".".$extension");
            }
        }
    }

    public function testGetCertsToCheck()
    {
        $certAlert = new CertAlert();
        $certs = $certAlert->getCertsToCheck();
        $testPaths = [
            '/tmp/crt.cer',
            '/tmp/test/crt.cer',
            '/tmp/crt.crt',
            '/tmp/test/crt.crt',
            '/tmp/crt.pem',
            '/tmp/test/crt.pem',
            '/tmp/crt.der',
            '/tmp/test/crt.der',
            '/tmp/crt.p7b',
            '/tmp/test/crt.p7b',
            '/tmp/crt.p7c',
            '/tmp/test/crt.p7c',
            '/tmp/crt.p12',
            '/tmp/test/crt.p12',
            '/tmp/crt.pfx',
            '/tmp/test/crt.pfx',
        ];
        foreach ($testPaths as $path) {
            $this->assertContains($path, $certs);
        }
    }
}











