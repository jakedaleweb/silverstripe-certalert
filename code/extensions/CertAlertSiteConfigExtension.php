<?php
class CertAlertSiteConfigExtension extends DataExtension
{
    private static $db = array(
        'CertAlertText' => 'HTMLText'
    );
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab("Root.CertAlert",
            new HTMLEditorField("CertAlertText", "Cert Alert Text")
        );
    }
}
