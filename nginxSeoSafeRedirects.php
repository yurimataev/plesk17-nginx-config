<?php if ($VAR->domain->isSeoRedirectToLanding or $VAR->domain->isSeoRedirectToWww) : ?>
server {
    listen <?php echo $OPT['ipAddress'] . ':' . $OPT['frontendPort'] . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;

    <?php foreach ($OPT['extra_ips'] as $ip) { 
        echo '    listen '.$ip.':' . $OPT['frontendPort'] . 
        ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') . ";\n"; 
    } ?>

    <?php if ($VAR->domain->isSeoRedirectToLanding) : ?>
    server_name www.<?php echo $VAR->domain->asciiName ?>;
    <?php elseif ($VAR->domain->isSeoRedirectToWww): ?>
    server_name <?php echo $VAR->domain->asciiName ?>;
    <?php endif; ?>

    return 301 <?php echo $OPT['redirect_scheme']; ?>://www.<?php echo $VAR->domain->asciiName ?>$request_uri;
}
<?php endif; ?>
<?php if ($VAR->domain->isAliasRedirected): ?>
<?php     foreach ($VAR->domain->webAliases AS $alias): ?>
<?php         if ($alias->isSeoRedirect) : ?>
server {
    listen <?php echo $OPT['ipAddress'] . ':' . $OPT['frontendPort'] . ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') ?>;

    <?php foreach ($OPT['extra_ips'] as $ip) { 
        echo '    listen '.$ip.':' . $OPT['frontendPort'] . 
        ($OPT['ssl'] ? ' ssl' : '') .
        ($OPT['ssl'] && $VAR->domain->physicalHosting->proxySettings['nginxHttp2'] ? ' http2' : '') . ";\n"; 
    } ?>

    server_name <?php echo $alias->asciiName ?>;
    server_name www.<?php echo $alias->asciiName ?>;

    return 301 <?php echo $OPT['redirect_scheme']; ?>://<?php echo $VAR->domain->targetName ?>$request_uri;
}
<?php         endif; ?>
<?php     endforeach; ?>
<?php endif; ?>