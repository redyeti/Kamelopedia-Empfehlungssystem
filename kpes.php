<?php

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install empf, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/empf/empf.php" );
EOT;
        exit( 1 );
}
 
# register the extension
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'KPES',
        'author' => 'J*',
        'url' => 'http://kamelopedia.mormo.org/index.php/Kamel:J*/KPES',
        'description' => 'Kamelopedia Empfehlungssystem',
        'descriptionmsg' => 'kpes_desc',
        'version' => '1.0beta',
);
 
$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
 
$wgAutoloadClasses['SpecialKPES'] = $dir . 'SpecialKPES.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['KPES'] = $dir . 'KPES.i18n.php'; # Location of a messages file (Tell MediaWiki to load this file)
$wgSpecialPages['KPES'] = 'SpecialKPES'; # Tell MediaWiki about the new special page and its class name


// add hooks

// add a "This page is recommended" icon or whatever; 
function KPESHook( &$article, &$outputDone, &$pcache )
{
	global $wgOut;

	$title = $article->getTitle();

	// get database access
	$dbr = wfGetDB( DB_SLAVE );

	// count recommendation pages linking to this page
	$qry = "
		SELECT count(*) AS counter
		FROM " . $dbr->tableName('pagelinks') . " as l1
		JOIN " . $dbr->tableName('page') . " AS p2 ON l1.pl_from = p2.page_id
		WHERE l1.pl_namespace = " . intval($title->getNamespace()) . " AND l1.pl_title = \"" . mysql_real_escape_string($title->getDBKey()) . "\" 
		AND p2.page_namespace = 2 AND p2.page_title LIKE \"%/Empfehlung\"
	";

	$res = $dbr->query( $qry );

	$count = 0;
	foreach( $res as $row ) {
		$count = $row->counter;
		break; // run this loop once
	}

	// if there are any pages, output the corresponding template
	if ( $count <> 0 )
	{
		$wgOut->addWikiText( "{{Empfohlene Seite|" . intval($count) . "}}");
	}

	return true;
}
$wgHooks['ArticleViewHeader'][] = 'KPESHook';


?>
