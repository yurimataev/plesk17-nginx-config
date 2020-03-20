# Plesk 17.X Nginx Templates Redirect Fix

By default, Plesk does not use best practices in its nginx config, notably using `if` directives for redirects (see [![If Is Evil](https://www.nginx.com/resources/wiki/start/topics/depth/ifisevil/)] for why you shouldn't do this!) This has been unaddressed since Plesk 12.5, despite the issue being brought up on Plesk support forums. These alternate Plesk templates fix the issue.

The files also include a hack that allows a virtual domain served by nginx to use multiple IPs. To use this feature, place a file called `extra_ips.php` in the same folder as `vhost.conf` for the domain in question. `extra_ips.php` needs to contain simply the following code:

```php
<?php

$extra_ips = array('1.1.1.1', '2.2.2.2');

?>
```

To install these templates, place:
`nginxSeoSafeRedirects.php` in `/usr/local/psa/admin/conf/templates/custom/domain/service`
`nginxDomainVirtualHost.php` in `/usr/local/psa/admin/conf/templates/custom/domain`

These templates have been tested and work correctly with Plesk 17.8.11 Update #83.

There is also [![a version that works with Plesk 12.5](https://github.com/yurimataev/plesk125-nginx-config)] .