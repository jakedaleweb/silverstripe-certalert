# silverstripe-certalert

SilverStripe module which configures email alerts for alerting to expiration of arbitrary x509 format certificates.

## Configuration

* `cert_paths` - The paths to the directory holding certificates on your server.
* `alert_time` - The time before your certificate expires to alert you i.e. set to `8 weeks` to be alerted 8 weeks before an expiration, if not set the default is `4 weeks`.
* `alert_addresses` - Email addresses to message regarding the expiration.

Configure via yml in your project the following:

```
CertAlert:
  cert_paths:
    - /some/where/in/my/file/system/
    - /some/other/path/
  alert_time: '8 weeks'
  alert_addresses:
    - me@myaddress.com
    - someone@else.com
```

Valid time units for `alert_time`:
```
| 'sec' | 'second' | 'min' | 'minute' | 'hour' | 'day' | 'fortnight' | 'forthnight' | 'month' | 'year' | 'secs' | 'seconds' | 'mins' | 'minutes' | 'hours' | 'days' | 'fortnights' | 'forthnights' | 'months' | 'years' | 'weeks' | 'daytext' |
```

Valid extensions for certificates which will be checked are:
```
| 'pem' | 'cer' | 'crt' | 'der' | 'p7b' | 'p7c' | 'p12' | 'pfx' |
```

An additional field is added in SiteConfig (settings) under Main.CertAlert this field can be used to add additional text such as links to documentation to the content of the emails sent, note that this field is generic and applied to all emails sent by this module in a project.
