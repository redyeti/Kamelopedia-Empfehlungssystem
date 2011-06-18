<?php

define('STD_METRIC', 'maxbased');

class EmpfQueryCreator {
	private $metric_name;
	private $dbr;

	function __construct($dbr) {
		$this->dbr = $dbr;
	}

	function createMetricPart($metric_name) {
		switch ($metric_name) {
			case "minbased":
				return Array(
					"SELECT" => "
					COUNT(page.page_id) /* gemeinsame Empfehlungen */ /
					(SELECT /* mögliche Empfehlungen */
						COUNT(*) AS num_entries 
						FROM " . $this->dbr->tableName( 'pagelinks' ) . " AS total
						WHERE total.pl_from = pagesrc.page_id
						OR total.pl_from = my_empf.page_id
						GROUP BY total.pl_from
						ORDER BY num_entries ASC
						LIMIT 1)",

					"JOIN" => "",
					"WHERE" => "",
				);
				break;

			case "maxbased":
				return Array(
					"SELECT" => "
					COUNT(page.page_id) /* gemeinsame Empfehlungen */ /
					(SELECT /* mögliche Empfehlungen */
						COUNT(*) AS num_entries 
						FROM " . $this->dbr->tableName( 'pagelinks' ) . " AS total
						WHERE total.pl_from = pagesrc.page_id
						OR total.pl_from = my_empf.page_id
						GROUP BY total.pl_from
						ORDER BY num_entries DESC
						LIMIT 1)",
					"JOIN" => "",
					"WHERE" => "",
				);
				break;
		}
	}


	function createSimilarCamelsQuery($camel, $metric_name) {
		$metric_part = $this->createMetricPart($metric_name);
		
		return "SELECT
				" . $metric_part["SELECT"] . " AS rate,
				pagesrc.page_title as page_title,
				pagesrc.page_id as page_id

				FROM " . $this->dbr->tableName( 'pagelinks' ) . " AS plf
				JOIN " . $this->dbr->tableName( 'page' ) . " AS pagesrc ON pagesrc.page_id = plf.pl_from
				JOIN " . $this->dbr->tableName( 'page' ) . " AS page ON page.page_namespace = plf.pl_namespace AND page.page_title = plf.pl_title

				JOIN " . $this->dbr->tableName( 'pagelinks' ) . " AS plf2 ON page.page_namespace = plf2.pl_namespace AND page.page_title = plf2.pl_title
				JOIN " . $this->dbr->tableName( 'page' ) . " AS my_empf ON plf2.pl_from = my_empf.page_id
				" . $metric_part["JOIN"] . "

				WHERE pagesrc.page_namespace = 2
				AND pagesrc.page_title LIKE \"%/Empfehlung\"
				AND pagesrc.page_id != my_empf.page_id
				AND my_empf.page_title = \"" . mysql_real_escape_string($camel) ."/Empfehlung\"
				" . $metric_part["WHERE"] . "
				GROUP BY pagesrc.page_id
				ORDER BY rate DESC";	
	}

	function createRecommendationsQuery($camel, $metric_name) {
		return "SELECT
				p.page_title AS page_title,
				p.page_namespace AS page_namespace,
				p.page_id AS page_id,
				SUM(sc.rate) AS rate
			FROM " . $this->dbr->tableName( 'page' ) . " AS p
			JOIN " . $this->dbr->tableName( 'pagelinks' ) . " AS plfr ON p.page_namespace = plfr.pl_namespace AND p.page_title = plfr.pl_title
			JOIN (" . $this->createSimilarCamelsQuery($camel, $metric_name) . ") AS sc ON sc.page_id = plfr.pl_from
			GROUP BY p.page_title
			HAVING (page_namespace, page_title) NOT IN (
				SELECT pls.pl_namespace AS page_namespace, pls.pl_title AS page_title
				FROM " . $this->dbr->tableName( 'pagelinks' ) . " AS pls
				JOIN " . $this->dbr->tableName( 'page' ) . " AS ps ON ps.page_id = pls.pl_from
				WHERE ps.page_namespace = 2 AND ps.page_title = \"" . mysql_real_escape_string($camel) ."/Empfehlung\")
			ORDER BY SUM(sc.rate) DESC";
	}

}


class SpecialEmpf extends SpecialPage {
        function __construct() {
                parent::__construct( 'Empf' );
                wfLoadExtensionMessages('Empf');
        }
 
        function execute( $par ) {
                global $wgRequest, $wgOut;
 
                $this->setHeaders();
 
                # Get request data from, e.g.
		$param = $wgRequest->getText('param');

		#TODO: falls $par leer ist, Auswahlfeld anzeigen und beenden

		$dbr = wfGetDB( DB_SLAVE );
		#$dbr->query("SELECT * FROM " . $dbr->tableName( 'page' ));

		$wgOut->addWikiText("==Kamele mit ähnlichen Empfehlungen==");

		$c = new EmpfQueryCreator($dbr);

		$res = $dbr->query( $c->createSimilarCamelsQuery($par, STD_METRIC) . " LIMIT 10");

		foreach( $res as $row ) {
        		$wgOut->addWikiText("\n* " . $row->page_title . ": " . $row->rate);
		}

		$wgOut->addWikiText("==Lesetipps==");
		
		$res = $dbr->query( $c->createRecommendationsQuery($par, STD_METRIC) . " LIMIT 10");

		foreach( $res as $row ) {
        		$wgOut->addWikiText("\n* " . $row->page_title . ": " . $row->rate);
		}

        }
}

?>
