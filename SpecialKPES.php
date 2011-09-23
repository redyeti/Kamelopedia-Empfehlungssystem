<?php

class SpecialKPES extends SpecialPage {
        function __construct() {
                parent::__construct( 'KPES' );
                wfLoadExtensionMessages('KPES');
        }
 
        function execute( $par ) {
                global $wgOut;

		$wgOut->setPagetitle(wfMsg('kpes'));
                $this->setHeaders();
 
		switch ($par)
		{
			case '':
				$this->showForms();
				break;
			case 'Highscore':
				$this->showHighscore();
				break;
			case 'Kamel':
				$this->showRecommendations();
				break;
			case 'Seite':
				$this->showPageInfo();
				break;
			default:
				$wgOut->addWikiText('Diese Aktion gibt es nicht.');
		}
	}

	
	private function showPageInfo()
	{
		global $wgOut, $wgRequest;

		// check if a page parameter is given
		$page = $wgRequest->getText('page');
		if (!$page)
		{
			$wgOut->showErrorPage('error','badarticleerror'); #FIXME
			return;
		}
		
		// check if page exists
		$title = Title::newFromText($page);
		if (is_null($title) or !$title->exists())
		{
			$wgOut->showErrorPage('error','badarticleerror'); #FIXME
			return;
		}
		
		// list recommending users

		$wgOut->addWikiText('==Kamele, die "' . $page . '" empfehlen==');

		// get database access
		$dbr = wfGetDB( DB_SLAVE );
		$qry = '
			SELECT p2.page_title
			FROM ' . $dbr->tableName('pagelinks') . ' as l1
			JOIN ' . $dbr->tableName('page') . ' AS p2 ON l1.pl_from = p2.page_id
			WHERE l1.pl_namespace = ' . intval($title->getNamespace()) . ' AND l1.pl_title = "' . mysql_real_escape_string($title->getDBKey()) . '" 
			AND p2.page_namespace = 2 AND p2.page_title LIKE "%/Empfehlung"';

		$res = $dbr->query( $qry );

		$out = '';
		foreach( $res as $row ) {
			$title2 = Title::newFromDBKey($row->page_title);
			$name = explode('/',$title2->getText());
        		$out .= "\n* [[{{ns:2}}:" . $row->page_title . '|' . $name[0] . ']]';
		}
		$wgOut->addWikiText($out);

		// list similar pages
		
		$wgOut->addWikiText('==Kamele, die "' . $page . '" gut finden, empfehlen auch==');

		$qry = '
			SELECT p3.page_namespace, p3.page_title, count(*)+RAND() as counter
			FROM ' . $dbr->tableName('pagelinks') . ' as l1
			JOIN ' . $dbr->tableName('pagelinks') . ' AS l2 ON l1.pl_from = l2.pl_from
			JOIN ' . $dbr->tableName('page') . ' as p2 ON l1.pl_from = p2.page_id
			JOIN ' . $dbr->tableName('page') . ' AS p3 ON l2.pl_title = p3.page_title AND l2.pl_namespace = p3.page_namespace
			WHERE l1.pl_namespace = ' . intval($title->getNamespace()) . ' AND l1.pl_title = "' . mysql_real_escape_string($title->getDBKey()) . '" 
			AND p2.page_namespace = 2 AND p2.page_title LIKE "%/Empfehlung"
			GROUP BY p3.page_id
			ORDER BY counter DESC
			LIMIT 1, 20';

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->query( $qry );

		$out = '';
		foreach( $res as $row ) {
			$title = Title::newFromDBKey($row->page_title);
        		$out .= "\n* [[:{{ns:" . intval($row->page_namespace) . '}}:' . $row->page_title . '|' . $title->getText() . ']] (' . intval($row->counter) . ' Empfehlungen)' ;
		}
		$wgOut->addWikiText($out);
	}

	// show input if KPES is run without parameters
	private function showForms()
	{
		global $wgOut, $wgScript, $wgUser;
	
		$wgOut->addWikiText('==Highscore==');
		
		$out = '';
		$out .= Xml::openElement( 'form', array( 'action' => $wgScript, 'method' => 'GET'  ) )
			. Xml::openElement( 'input', array( 'type' => 'hidden', 'name' => 'title', 'value' => $this->getTitle()->getPrefixedText().'/Highscore' ))
			. Xml::openElement( 'input', array( 'type' => 'submit', 'value' => 'Highscore anzeigen'))
			. Xml::closeElement( 'form' );
		$wgOut->addHTML($out);

		$wgOut->addWikiText("\n\n==Wikis persönliche Empfehlungen==");
		
		$out = '';
		$out .= Xml::openElement( 'form', array( 'action' => $wgScript, 'method' => 'GET'  ) )
			. Xml::openElement( 'input', array( 'type' => 'hidden', 'name' => 'title', 'value' => $this->getTitle()->getPrefixedText().'/Kamel' ))
			. 'Persönliche Empfehlungen für '
			. Xml::openElement( 'input', array( 'type' => 'text', 'name' => 'camel', 'value' => $wgUser->getName() ))
			. ' anzeigen'
			. '<br/>'
			. Xml::openElement( 'input', array( 'type' => 'checkbox', 'name' => 'random', 'value' => 'all', 'id' => 'random' ))
			. '<label for="random">Gewichtete Zufallsergebnisse anstatt tatsächlicher Rangfolge anzeigen</label>'
			. '<br/>'
			. Xml::openElement( 'input', array( 'type' => 'submit', 'value' => 'Anzeigen'))
			. Xml::closeElement( 'form' );
		$wgOut->addHTML($out);

		$wgOut->addWikiText("\n\n==Seiteninfo==");
		$out = '';
		$out .= Xml::openElement( 'form', array( 'action' => $wgScript, 'method' => 'GET'  ) )
			. Xml::openElement( 'input', array( 'type' => 'hidden', 'name' => 'title', 'value' => $this->getTitle()->getPrefixedText().'/Seite' ))
			. 'Herausfinden, welche Kamele '
			. Xml::openElement( 'input', array( 'type' => 'text', 'name' => 'page', 'value' => 'Hauptseite' ))
			. ' empfehlen.<br/>'
			. Xml::openElement( 'input', array( 'type' => 'submit', 'value' => 'Anzeigen'))
			. Xml::closeElement( 'form' );
		$wgOut->addHTML($out);
	
		return;
	}

	private function showHighscore()
	{
		global $wgOut;

		$wgOut->addWikiText('==Meistempfohlene Seiten==');

		$dbr = wfGetDB( DB_SLAVE );
		
		$res = $dbr->query( $this->createHighscoreQuery($dbr) . ' LIMIT 30');
		$out = '';

		foreach( $res as $row ) {

			$title = Title::newFromDBKey($row->page_title);
			$name = explode('/',$title->getText());
			
        		$out .= "\n# [[:{{ns:" . intval($row->page_namespace) . '}}:' . $row->page_title . '|' . $name[0] . ']] (' . intval($row->counter) . ' Empfehlungen)';
		}

		$wgOut->addWikiText($out);
	}


	private function showRecommendations()
	{
		global $wgRequest, $wgUser, $wgOut;

		// check if camel parameter is set, otherwise use current user
		$user = $wgRequest->getText('camel');
		if (!$user)
			$user = $wgUser->getName();

		$rand_mode = $wgRequest->getText('random');

		$wgOut->addWikiText('==Kamele mit ähnlichem Geschmack wie '.$user.'==');

		// get database access
		$dbr = wfGetDB( DB_SLAVE );
		
		$res = $dbr->query( $this->createSimCamelsQuery($dbr, $user) . ' LIMIT 10');

		$out = '';
		foreach( $res as $row ) {
			$title = Title::newFromDBKey($row->page_title);
			$name = explode('/',$title->getText());
        		$out .= "\n# [[{{ns:2}}:" . $row->page_title . '|' . $name[0] . ']] (' . round(floatval($row->metric)*100,1) . ' Punkte)';
		}
		$wgOut->addWikiText($out);


		$wgOut->addWikiText('==Leseempfehlungen für '.$user.'==');

		$res = $dbr->query( $this->createRecommendationsQuery($dbr, $user, $rand_mode) . ' LIMIT 20');
		$out = '';

		foreach( $res as $row ) {
			$title = Title::newFromDBKey($row->page_title);
        		$out .= "\n# [[:{{ns:" . $row->page_namespace . '}}:' . $row->page_title . '|' . $title->getText() . ']] (' . round(floatval($row->metric),1) . ' Punkte)';
		}
		$wgOut->addWikiText($out);

	}

	// create the (sub-) query for highscore
	private function createHighscoreQuery($dbr)
	{
		return '
			SELECT p2.page_title AS page_title,
			p2.page_namespace AS page_namespace,
			count(*) as counter
			FROM ' . $dbr->tableName('page') . ' AS p0
			JOIN ' . $dbr->tableName('pagelinks') . ' AS l1 ON p0.page_id = l1.pl_from
			JOIN ' . $dbr->tableName('page') . ' AS p2 ON l1.pl_namespace = p2.page_namespace AND l1.pl_title = p2.page_title
			WHERE p0.page_namespace = 2 AND p0.page_title LIKE "%/Empfehlung"
			GROUP BY p2.page_id
			ORDER BY counter DESC';

		/*
		p0 (irgendeine Kamelempfehlung)
		v (Link)
		p2 (Seite)
		*/

	}

	// create the (sub-) query for recommendations
	private function createRecommendationsQuery($dbr, $refCamel, $rand_mode)
	{

		$rand = '+0.001*RAND()';
		switch ($rand_mode)
		{
			case 'all':
				$rand = '*RAND()';
				break;
			case 'none':
				$rand = '';
				break;
		}

		return '
			SELECT distinct p10.pl_namespace as page_namespace, p10.pl_title as page_title, SUM(sim.metric)*100 as metric, SUM(sim.metric)*100' . $rand . ' AS randmetric
			FROM (' . $this->createSimCamelsQuery($dbr, $refCamel) . ') AS sim
			JOIN ' . $dbr->tableName('pagelinks') . ' AS p10 ON sim.page_id = p10.pl_from
			GROUP BY p10.pl_title
			ORDER BY randmetric DESC
		';
		//TODO: exclude own recommendations
	}	

	// create the (sub-) query for camels with similar recommendations
	private function createSimCamelsQuery($dbr, $refCamel)
	{
		/*
		Kurzerklärung:

		p0: eigene Empfehlungsseite
		 v (l1)
		p2: gemeinsam empfohlene Seite
		 ^ (l2)
		p3: andere Empfehlungsseite
		 v (l4)
		*/	

		return '
			SELECT
				count(distinct p2.page_id) AS counter,
				count(distinct p4.page_id) AS total,
				count(distinct p2.page_id) / count(distinct p4.page_id) AS metric,
				p3.page_title,
				p3.page_id
			FROM ' . $dbr->tableName('page') . ' AS p0
			JOIN ' . $dbr->tableName('pagelinks') . ' AS l1 ON p0.page_id = l1.pl_from
			JOIN ' . $dbr->tableName('page') . ' AS p2 ON l1.pl_title = p2.page_title AND l1.pl_namespace = p2.page_namespace
			JOIN ' . $dbr->tableName('pagelinks') . ' AS l2 ON l1.pl_title = l2.pl_title AND l1.pl_namespace = l2.pl_namespace
			JOIN ' . $dbr->tableName('page') . ' AS p3 ON l2.pl_from = p3.page_id
			JOIN ' . $dbr->tableName('pagelinks') . ' AS l4 ON p3.page_id = l4.pl_from
			JOIN ' . $dbr->tableName('page') . ' AS p4 ON p4.page_title = l4.pl_title AND p4.page_namespace = l4.pl_namespace
			WHERE p0.page_namespace = 2 AND p0.page_title = "' . mysql_real_escape_string($refCamel) . '/Empfehlung" /* meine Seite */
			AND p0.page_id != p3.page_id
			AND p3.page_namespace = 2 AND p3.page_title LIKE "%/Empfehlung"
			GROUP BY p3.page_title
			HAVING counter > 1
			ORDER BY metric DESC';
	}

}

?>
