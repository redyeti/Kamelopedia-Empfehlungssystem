<?php

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install empf, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/empf/empf.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'empf',
        'author' => 'J*',
        'url' => 'kamelopedia.mormo.org/index.php/Kamel:J*/empf',
        'description' => 'ähnliche Empfehlungen finden',
        'descriptionmsg' => 'empf-desc',
        'version' => '0.0.0',
);
 
$dir = dirname(__FILE__) . '/';
 
$wgAutoloadClasses['SpecialEmpf'] = $dir . 'SpecialEmpf.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['Empf'] = $dir . 'Empf.i18n.php'; # Location of a messages file (Tell MediaWiki to load this file)
$wgSpecialPages['Empf'] = 'SpecialEmpf'; # Tell MediaWiki about the new special page and its class name
?>
