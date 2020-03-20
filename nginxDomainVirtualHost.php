<?php
/**
 * @var Template_VariableAccessor $VAR
 * @var array $OPT
 */
?>

<?php
	if ($VAR->domain->physicalHosting->sslRedirect or $OPT['ssl'])
			$OPT['redirect_scheme'] = 'https';
	else 	$OPT['redirect_scheme'] = 'http';
?>

<?php
  /* Custom hack for Apache to listen on multiple IPs */
	/* Conf file path */
	$conf_path = $VAR->domain->physicalHosting->customConfigFile;
	$conf_path = str_replace('/vhost.conf','',$conf_path);

  /* Load extra_ips file */
	$extra_ips_file = $conf_path.'/extra_ips.php';
	if (is_file($extra_ips_file)) include($extra_ips_file);
?>

<?php echo $VAR->includeTemplate('domain/service/nginxSeoSafeRedirects.php', array('ipAddress' => $OPT['ipAddress']->escapedAddress, 'ssl' => $OPT['ssl'], 'frontendPort' => $OPT['frontendPort'], 'redirect_scheme' => $OPT['redirect_scheme'], 'extra_ips' => $extra_ips)); ?>

server {
    listen <?php echo $OPT['ipAddress']->escapedAddress . ':' . $OPT['frontendPort'] .
        ($OPT['default'] ? ' default_server' : '') . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;

<?php
	/* Custom hack for Apache to listen on multiple IPs */
	foreach ($extra_ips as $ip) {
		echo '    listen '.$ip.':' . $OPT['frontendPort'] .
		($OPT['default'] ? ' default_server' : '') .
		($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') . ";\n";
	}
?>

    server_name <?php echo $VAR->domain->asciiName ?>;
<?php if ($VAR->domain->isWildcard): ?>
    server_name ~^<?php echo $VAR->domain->pcreName ?>$;
<?php else: ?>
<?php   if ($VAR->domain->isSeoRedirectToLanding) : ?>
    server_name <?php echo $VAR->domain->asciiName ?>;
<?php   elseif ($VAR->domain->isSeoRedirectToWww): ?>
    server_name www.<?php echo $VAR->domain->asciiName ?>;
<?php   else: ?>
	server_name <?php echo $VAR->domain->asciiName ?>;
	server_name www.<?php echo $VAR->domain->asciiName ?>;
<?php   endif; ?>
    <?php if ($OPT['ipAddress']->isIpV6()): ?>
    server_name ipv6.<?php echo $VAR->domain->asciiName ?>;
    <?php else: ?>
    server_name ipv4.<?php echo $VAR->domain->asciiName ?>;
    <?php endif ?>
<?php endif ?>
<?php if ($VAR->domain->webAliases): ?>
    <?php foreach ($VAR->domain->webAliases as $alias): ?>
	<?php if (!$alias->isSeoRedirect) : ?>
    server_name <?php echo $alias->asciiName ?>;
    server_name www.<?php echo $alias->asciiName ?>;
	<?php endif ?>
    <?php endforeach ?>
<?php endif ?>
<?php if ($VAR->domain->previewDomainName): ?>
    server_name "<?php echo $VAR->domain->previewDomainName ?>";
<?php endif ?>

<?php if ($OPT['ssl']): ?>
<?php $sslCertificate = $VAR->server->sni && $VAR->domain->physicalHosting->sslCertificate ?
    $VAR->domain->physicalHosting->sslCertificate :
    $OPT['ipAddress']->sslCertificate; ?>
    <?php if ($sslCertificate->ce): ?>
    ssl_certificate             <?php echo $sslCertificate->ceFilePath ?>;
    ssl_certificate_key         <?php echo $sslCertificate->ceFilePath ?>;
        <?php if ($sslCertificate->ca): ?>
    ssl_client_certificate      <?php echo $sslCertificate->caFilePath ?>;
        <?php endif ?>
    <?php endif ?>
<?php endif ?>

<?php if (!empty($VAR->domain->physicalHosting->proxySettings['nginxClientMaxBodySize'])): ?>
    client_max_body_size <?php echo $VAR->domain->physicalHosting->proxySettings['nginxClientMaxBodySize'] ?>;
<?php endif ?>

<?php if ($VAR->domain->physicalHosting->scriptTimeout): ?>
    proxy_read_timeout <?php echo min($VAR->domain->physicalHosting->scriptTimeout, 2147483) ?>;
<?php endif ?>

<?php if (!$OPT['ssl'] && $VAR->domain->physicalHosting->ssl && $VAR->domain->physicalHosting->sslRedirect): ?>

<?php /* echo $VAR->includeTemplate('domain/service/nginxSeoSafeRedirects.php', array('ssl' => true))*/ ?>

        return 301 https://$host$request_uri;
    }
    <?php return ?>
<?php endif ?>

    root "<?php echo $OPT['ssl'] ? $VAR->domain->physicalHosting->httpsDir : $VAR->domain->physicalHosting->httpDir ?>";
    access_log "<?php echo $VAR->domain->physicalHosting->logsDir . '/' . ($OPT['ssl'] ? 'proxy_access_ssl_log' : 'proxy_access_log') ?>";
    error_log "<?php echo $VAR->domain->physicalHosting->logsDir . '/proxy_error_log' ?>";

<?php if ($OPT['default']): ?>
    <?php echo $VAR->includeTemplate('service/nginxSitePreview.php') ?>
<?php endif ?>

<?php echo $VAR->domain->physicalHosting->proxySettings['allowDeny'] ?>

<?php /* echo $VAR->includeTemplate('domain/service/nginxSeoSafeRedirects.php', $OPT) */ ?>

<?=$VAR->includeTemplate('domain/service/nginxCache.php', $OPT)?>

<?php echo $VAR->domain->physicalHosting->nginxExtensionsConfigs ?>

<?php if ($VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    location / {
    <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT) ?>
    }

    <?php if (!$VAR->domain->physicalHosting->proxySettings['nginxTransparentMode'] && !$VAR->domain->physicalHosting->proxySettings['nginxServeStatic']): ?>
    location /internal-nginx-static-location/ {
        alias <?php echo $OPT['documentRoot'] ?>/;
        internal;
        <?php if ($VAR->domain->physicalHosting->expires && $VAR->domain->physicalHosting->expiresStaticOnly): ?>
        expires <?=$VAR->quote($VAR->domain->physicalHosting->expires)?>;
        <?php endif ?>
    }
    <?php endif ?>
<?php else: ?>
    <?php if ($VAR->domain->physicalHosting->directoryIndex): ?>
    location / {
        index <?=$VAR->quote($VAR->domain->physicalHosting->directoryIndex)?>;
        # try_files $uri $uri/ /index.php?$args; # If uncommenting again, post description of why. Is it for when using nginx as proxy?
		 # It's for Wordpress, I'm pretty sure
    }
    <?php endif ?>
    # Caching:
    # Media: images, icons, video, audio, HTC
    location ~* \.(?:jpg|jpeg|gif|png|ico|cur|gz|svg|svgz|mp4|ogg|ogv|webm|htc)$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public";
    }
    # CSS and Javascript
    location ~* \.(?:css|js)$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public";
    }
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->hasWebstat): ?>
    <?php echo $VAR->includeTemplate('domain/service/nginxWebstatDirectories.php', $OPT) ?>
<?php endif ?>

<?php if ($VAR->domain->active && !$VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    <?php echo $VAR->includeTemplate('domain/service/nginxProtectedDirectories.php', $OPT) ?>
<?php else: ?>
    <?php echo $VAR->includeTemplate('domain/service/nginxProtectedDirectoriesProxy.php', $OPT) ?>
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->proxySettings['fileSharingPrefix']
    && $VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    location ~ "^/<?php echo $VAR->domain->physicalHosting->proxySettings['fileSharingPrefix'] ?>/" {
        <?=$VAR->includeTemplate('domain/service/proxy.php', $OPT + ['nginxCacheEnabled' => false])?>
    }
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->proxySettings['nginxServeStatic']): ?>
    location @fallback {
        <?php if ($VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
            <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT) ?>
        <?php else: ?>
        return 404;
        <?php endif ?>
    }

    location ~ ^/(.*\.(<?php echo $VAR->domain->physicalHosting->proxySettings['nginxStaticExtensions'] ?>))$ {
        try_files $uri @fallback;
        <?php if ($VAR->domain->physicalHosting->expires && $VAR->domain->physicalHosting->expiresStaticOnly): ?>
        expires <?=$VAR->quote($VAR->domain->physicalHosting->expires)?>;
        <?php endif ?>
    }
<?php endif ?>

<?php if ($VAR->domain->active && $VAR->domain->physicalHosting->php && $VAR->domain->physicalHosting->proxySettings['nginxServePhp']): ?>
    location ~ ^/~(.+?)(/.*?\.php)(/.*)?$ {
        alias <?php echo $VAR->domain->physicalHosting->webUsersDir ?>/$1/$2;
        <?php echo $VAR->includeTemplate('domain/service/fpm.php', $OPT) ?>
    }

        <?php if ($VAR->domain->physicalHosting->proxySettings['nginxProxyMode']): ?>
    location ~ ^/~(.+?)(/.*)?$ {
            <?php echo $VAR->includeTemplate('domain/service/proxy.php', $OPT) ?>
    }
        <?php endif ?>

    location ~ \.php(/.*)?$ {
        <?php echo $VAR->includeTemplate('domain/service/fpm.php', $OPT) ?>
    }

        <?php if (FALSE && $VAR->domain->physicalHosting->directoryIndex): ?>
    location ~ /$ {
        index <?=$VAR->quote($VAR->domain->physicalHosting->directoryIndex)?>;
    }
        <?php endif ?>
<?php endif ?>

<?php if ($VAR->domain->physicalHosting->restrictFollowSymLinks): ?>
    disable_symlinks if_not_owner from=$document_root;
<?php endif ?>

<?php if ($VAR->domain->physicalHosting->expires && !$VAR->domain->physicalHosting->expiresStaticOnly): ?>
    expires <?=$VAR->quote($VAR->domain->physicalHosting->expires)?>;
<?php endif ?>

<?php foreach ((array)$VAR->domain->physicalHosting->headers as list($name, $value)): ?>
    add_header <?=$VAR->quote([$name, $value])?>;
<?php endforeach ?>
    add_header X-Powered-By PleskLin;

<?php if (is_file($VAR->domain->physicalHosting->customNginxConfigFile)): ?>
    include "<?php echo $VAR->domain->physicalHosting->customNginxConfigFile ?>";
<?php endif ?>
}
