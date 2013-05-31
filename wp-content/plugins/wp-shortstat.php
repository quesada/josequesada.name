<?php 
/*
Plugin Name: WP-ShortStat
Plugin URI: http://dev.wp-plugins.org/wiki/wp-shortstat
Description: Track your blog stats. Based on <a href="http://shortstat.shauninman.com/">Shaun Inman's ShortStat</a>.
Version: 1.3
Author: Jeff Minard
Author URI: http://jrm.cc/
*/

if( !function_exists('is_admin_page') ) {
	function is_admin_page() {
		if (function_exists('check_admin_referer')) {
			return true;
		}
		else {
			return false;
		}
	}
}

class wp_shortstat {

	var $languages;
	var $table_stats;
	var $table_search;
	var $tz_offset;
	
	function wp_shortstat() {	// Constructor -- Set things up.
		global $table_prefix;
		
		// tables
		$this->table_stats  = $table_prefix . "ss_stats";
		$this->table_search = $table_prefix . "ss_search";
		
		$this->tz_offset = get_settings('gmt_offset');
		
		// Longest Array Line Ever...
		$this->languages = array( "af" => "Afrikaans", "sq" => "Albanian", "eu" => "Basque", "bg" => "Bulgarian", "be" => "Byelorussian", "ca" => "Catalan", "zh" => "Chinese", "zh-cn" => "Chinese/China", "zh-tw" => "Chinese/Taiwan", "zh-hk" => "Chinese/Hong Kong", "zh-sg" => "Chinese/singapore", "hr" => "Croatian", "cs" => "Czech", "da" => "Danish", "nl" => "Dutch", "nl-nl" => "Dutch/Netherlands", "nl-be" => "Dutch/Belgium", "en" => "English", "en-gb" => "English/United Kingdom", "en-us" => "English/United States", "en-au" => "English/Australian", "en-ca" => "English/Canada", "en-nz" => "English/New Zealand", "en-ie" => "English/Ireland", "en-za" => "English/South Africa", "en-jm" => "English/Jamaica", "en-bz" => "English/Belize", "en-tt" => "English/Trinidad", "et" => "Estonian", "fo" => "Faeroese", "fa" => "Farsi", "fi" => "Finnish", "fr" => "French", "fr-be" => "French/Belgium", "fr-fr" => "French/France", "fr-ch" => "French/Switzerland", "fr-ca" => "French/Canada", "fr-lu" => "French/Luxembourg", "gd" => "Gaelic", "gl" => "Galician", "de" => "German", "de-at" => "German/Austria", "de-de" => "German/Germany", "de-ch" => "German/Switzerland", "de-lu" => "German/Luxembourg", "de-li" => "German/Liechtenstein", "el" => "Greek", "he" => "Hebrew", "he-il" => "Hebrew/Israel", "hi" => "Hindi", "hu" => "Hungarian", "ie-ee" => "Internet Explorer/Easter Egg", "is" => "Icelandic", "id" => "Indonesian", "in" => "Indonesian", "ga" => "Irish", "it" => "Italian", "it-ch" => "Italian/ Switzerland", "ja" => "Japanese", "ko" => "Korean", "lv" => "Latvian", "lt" => "Lithuanian", "mk" => "Macedonian", "ms" => "Malaysian", "mt" => "Maltese", "no" => "Norwegian", "pl" => "Polish", "pt" => "Portuguese", "pt-br" => "Portuguese/Brazil", "rm" => "Rhaeto-Romanic", "ro" => "Romanian", "ro-mo" => "Romanian/Moldavia", "ru" => "Russian", "ru-mo" => "Russian /Moldavia", "gd" => "Scots Gaelic", "sr" => "Serbian", "sk" => "Slovack", "sl" => "Slovenian", "sb" => "Sorbian", "es" => "Spanish", "es-do" => "Spanish", "es-ar" => "Spanish/Argentina", "es-co" => "Spanish/Colombia", "es-mx" => "Spanish/Mexico", "es-es" => "Spanish/Spain", "es-gt" => "Spanish/Guatemala", "es-cr" => "Spanish/Costa Rica", "es-pa" => "Spanish/Panama", "es-ve" => "Spanish/Venezuela", "es-pe" => "Spanish/Peru", "es-ec" => "Spanish/Ecuador", "es-cl" => "Spanish/Chile", "es-uy" => "Spanish/Uruguay", "es-py" => "Spanish/Paraguay", "es-bo" => "Spanish/Bolivia", "es-sv" => "Spanish/El salvador", "es-hn" => "Spanish/Honduras", "es-ni" => "Spanish/Nicaragua", "es-pr" => "Spanish/Puerto Rico", "sx" => "Sutu", "sv" => "Swedish", "sv-se" => "Swedish/Sweden", "sv-fi" => "Swedish/Finland", "ts" => "Thai", "tn" => "Tswana", "tr" => "Turkish", "uk" => "Ukrainian", "ur" => "Urdu", "vi" => "Vietnamese", "xh" => "Xshosa", "ji" => "Yiddish", "zu" => "Zulu");
		
	}
	
	function setup() { // get the DB ready if it isn't already.
		
		if( !function_exists('maybe_create_table') ) {
			function maybe_create_table($table_name, $create_ddl) {
			    global $wpdb;
			    foreach ($wpdb->get_col("SHOW TABLES",0) as $table ) {
			        if ($table == $table_name) return true;
		        }
			    $q = $wpdb->query($create_ddl);
			    // we cannot directly tell that whether this succeeded!
			    foreach ($wpdb->get_col("SHOW TABLES",0) as $table ) {
			        if ($table == $table_name) return true;
			    }
			    return false;
			}
		}
				
		$table_stats_query = "CREATE TABLE $this->table_stats (
							  id int(11) unsigned NOT NULL auto_increment,
							  remote_ip varchar(15) NOT NULL default '',
							  country varchar(50) NOT NULL default '',
							  language VARCHAR(5) NOT NULL default '',
							  domain varchar(255) NOT NULL default '',
							  referer varchar(255) NOT NULL default '',
							  resource varchar(255) NOT NULL default '',
							  user_agent varchar(255) NOT NULL default '',
							  platform varchar(50) NOT NULL default '',
							  browser varchar(50) NOT NULL default '',
							  version varchar(15) NOT NULL default '',
							  dt int(10) unsigned NOT NULL default '0',
							  UNIQUE KEY id (id)
							  ) TYPE=MyISAM";
			  
		$table_search_query = "CREATE TABLE $this->table_search (
							  id int(11) unsigned NOT NULL auto_increment,
							  searchterms varchar(255) NOT NULL default '',
							  count int(10) unsigned NOT NULL default '0',
							  PRIMARY KEY  (id)
							  ) TYPE=MyISAM;";
		
		maybe_create_table($this->table_stats, $table_stats_query);
		maybe_create_table($this->table_search, $table_search_query);
		
	// end wp_shortstat();
	}
	
	function track() {	// Only public function
		global $wpdb;
		
		if($wpdb->is_admin 
		|| strstr($_SERVER['PHP_SELF'], 'wp-admin/')
		|| is_404()
		|| is_admin_page()
		  )return; // let's not track the admin pages -- no one cares.
		
		$ip		= $_SERVER['REMOTE_ADDR'];
		$cntry	= $this->determineCountry($ip);
		$lang	= $this->determineLanguage();
		$ref	= $_SERVER['HTTP_REFERER'];
		$url 	= parse_url($ref);
		$domain	= eregi_replace("^www.","",$url['host']);
		$res	= $_SERVER['REQUEST_URI'];
		$ua		= $_SERVER['HTTP_USER_AGENT'];
		$br		= $this->parseUserAgent($ua);
		$dt		= time();
		
		$this->sniffKeywords($url);
		
		$query = "INSERT INTO $this->table_stats (remote_ip,country,language,domain,referer,resource,user_agent,platform,browser,version,dt) 
				  VALUES ('$ip','$cntry','$lang','$domain','$ref','$res','$ua','$br[platform]','$br[browser]','$br[version]',$dt)";
		
		$wpdb->query($query);
	}
	
	function determineCountry($ip) {
		
		$coinfo = @file('http://www.hostip.info/api/get.html?ip=' . $ip);
		$country_string = explode(':',$coinfo[0]);
		$country = trim($country_string[1]);
		
		if($country == '(Private Address) (XX)' 
		|| $country == '(Unknown Country?) (XX)' 
		|| $country == '' 
		|| !$country 
		  )return 'Indeterminable';
			
		return $country;
		
	}
	
	function sniffKeywords($url) { // $url should be an array created by parse_url($ref)
		global $wpdb;
		
		// Check for google first
		if (preg_match("/google\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// Googles search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/yahoo\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// Yahoo search terms are in "p"
			$searchterms = $q['p'];
			}
		else if (preg_match("/search\.msn\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// MSN search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/search\.aol\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// AOL search terms are in "query"
			$searchterms = $q['query'];
			}
		else if (preg_match("/web\.ask\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// Ask Jeeves search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/search\.looksmart\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// LookSmart search terms are in "p"
			$searchterms = $q['p'];
			}
		else if (preg_match("/alltheweb\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// All the Web search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/a9\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// A9 search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/gigablast\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// Gigablast search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/s\.teoma\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// Teoma search terms are in "q"
			$searchterms = $q['q'];
			}
		else if (preg_match("/clusty\./i", $url['host'])) {
			parse_str($url['query'],$q);
			// Clusty search terms are in "query"
			$searchterms = $q['query'];
			}
		
		if (isset($searchterms) && !empty($searchterms)) {
			// Remove BINARY from the SELECT statement for a case-insensitive comparison
			$exists_query = "SELECT id FROM $this->table_search WHERE searchterms = BINARY '$searchterms'";
			$search_term_id = $wpdb->get_var($exists_query);
			
			if( $search_term_id ) {
				$query = "UPDATE $this->table_search SET count = (count+1) WHERE id = $search_term_id";
			} else {
				$query = "INSERT INTO $this->table_search (searchterms,count) VALUES ('$searchterms',1)";
			}
			
			$wpdb->query($query);
		}
	}
	
	function parseUserAgent($ua) {
		$browser['platform']	= "Indeterminable";
		$browser['browser']		= "Indeterminable";
		$browser['version']		= "Indeterminable";
		$browser['majorver']	= "Indeterminable";
		$browser['minorver']	= "Indeterminable";
		
		// Test for platform
		if (eregi('Win95',$ua)) {
			$browser['platform'] = "Windows 95";
			}
		else if (eregi('Win98',$ua)) {
			$browser['platform'] = "Windows 98";
			}
		else if (eregi('Win 9x 4.90',$ua)) {
			$browser['platform'] = "Windows ME";
			}
		else if (eregi('Windows NT 5.0',$ua)) {
			$browser['platform'] = "Windows 2000";
			}
		else if (eregi('Windows NT 5.1',$ua)) {
			$browser['platform'] = "Windows XP";
			}
		else if (eregi('Windows NT 5.2',$ua)) {
			$browser['platform'] = "Windows 2003";
			}
		else if (eregi('Windows NT 6.0',$ua)) {
			$browser['platform'] = "Windows Longhorn beta";
			}
		else if (eregi('Windows',$ua)) {
			$browser['platform'] = "Windows";
			}
		else if (eregi('Mac OS X',$ua)) {
			$browser['platform'] = "Mac OS X";
			}
		else if (eregi('Macintosh',$ua)) {
			$browser['platform'] = "Mac OS Classic";
			}
		else if (eregi('Linux',$ua)) {
			$browser['platform'] = "Linux";
			}
		else if (eregi('BSD',$ua) || eregi('FreeBSD',$ua) || eregi('NetBSD',$ua)) {
		$browser['platform'] = "BSD";
			}
		else if (eregi('SunOS',$ua)) {
			$browser['platform'] = "Solaris";
			}

		// Test for browser type
		if (eregi('Mozilla/4',$ua) && !eregi('compatible',$ua)) {
			$browser['browser'] = "Netscape";
			eregi('Mozilla/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Mozilla/5',$ua) || eregi('Gecko',$ua)) {
			$browser['browser'] = "Mozilla";
			eregi('rv(:| )([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[2];
			}
		if (eregi('Safari',$ua)) {
			$browser['browser'] = "Safari";
			$browser['platform'] = "Mac OS X";
			eregi('Safari/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];

			if (eregi('412',$browser['version'])) {
				$browser['version'] 	= 2.0;
				$browser['majorver']	= 2;
				$browser['minorver']	= 0;
				}
			else if (eregi('312',$browser['version'])) {
				$browser['version'] 	= 1.3;
				$browser['majorver']	= 1;
				$browser['minorver']	= 3;
				}
			else if (eregi('125',$browser['version'])) {
				$browser['version'] 	= 1.2;
				$browser['majorver']	= 1;
				$browser['minorver']	= 2;
				}
			else if (eregi('100',$browser['version'])) {
				$browser['version'] 	= 1.1;
				$browser['majorver']	= 1;
				$browser['minorver']	= 1;
				}
			else if (eregi('85',$browser['version'])) {
				$browser['version'] 	= 1.0;
				$browser['majorver']	= 1;
				$browser['minorver']	= 0;
				}
			else if ($browser['version']<85) {
				$browser['version'] 	= "1.0 beta";
				}
			}
		if (eregi('iCab',$ua)) {
			$browser['browser'] = "iCab";
			eregi('iCab ([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Firefox',$ua)) {
			$browser['browser'] = "Firefox";
			eregi('Firefox/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Firebird',$ua)) {
			$browser['browser'] = "Firebird";
			eregi('Firebird/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Phoenix',$ua)) {
			$browser['browser'] = "Phoenix";
			eregi('Phoenix/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Camino',$ua)) {
			$browser['browser'] = "Camino";
			eregi('Camino/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Chimera',$ua)) {
			$browser['browser'] = "Chimera";
			eregi('Chimera/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Netscape',$ua)) {
			$browser['browser'] = "Netscape";
			eregi('Netscape[0-9]?/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('MSIE',$ua)) {
			$browser['browser'] = "Internet Explorer";
			eregi('MSIE ([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('MSN Explorer',$ua)) {
			$browser['browser'] = "MSN Explorer";
			eregi('MSN Explorer ([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('AOL',$ua)) {
			$browser['browser'] = "AOL";
			eregi('AOL ([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('America Online Browser',$ua)) {
			$browser['browser'] = "AOL Browser";
			eregi('America Online Browser ([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('K-Meleon',$ua)) {
			$browser['browser'] = "K-Meleon";
			eregi('K-Meleon/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Beonex',$ua)) {
			$browser['browser'] = "Beonex";
			eregi('Beonex/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Opera',$ua)) {
			$browser['browser'] = "Opera";
			eregi('Opera( |/)([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[2];
			}
		if (eregi('OmniWeb',$ua)) {
			$browser['browser'] = "OmniWeb";
			eregi('OmniWeb/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];

			if (eregi('563',$browser['version'])) {
				$browser['version'] 	= 5.1;
				$browser['majorver']	= 5;
				$browser['minorver']	= 1;
				}
			else if (eregi('558',$browser['version'])) {
				$browser['version'] 	= 5.0;
				$browser['majorver']	= 5;
				$browser['minorver']	= 0;
				}
			else if (eregi('496',$browser['version'])) {
				$browser['version'] 	= 4.5;
				$browser['majorver']	= 4;
				$browser['minorver']	= 5;
				}
			}
		if (eregi('Konqueror',$ua)) {
			$browser['platform'] = "Linux";
	
			$browser['browser'] = "Konqueror";
			eregi('Konqueror/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Galeon',$ua)) {
			$browser['browser'] = "Galeon";
			eregi('Galeon/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Epiphany',$ua)) {
			$browser['browser'] = "Epiphany";
			eregi('Epiphany/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Kazehakase',$ua)) {
			$browser['browser'] = "Kazehakase";
			eregi('Kazehakase/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('amaya',$ua)) {
			$browser['browser'] = "Amaya";
			eregi('amaya/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Crawl',$ua) || eregi('bot',$ua) || eregi('slurp',$ua) || eregi('spider',$ua)) {
			$browser['browser'] = "Crawler/Search Engine";
			}
		if (eregi('Lynx',$ua)) {
			$browser['browser'] = "Lynx";
			eregi('Lynx/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('Links',$ua)) {
			$browser['browser'] = "Links";
			eregi('\(([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		if (eregi('ELinks',$ua)) {
			$browser['browser'] = "ELinks";
			eregi('ELinks/([[:digit:]\.]+)',$ua,$b);
			$browser['version'] = $b[1];
			}
		
		// Determine browser versions
		if (($browser['browser']!='AppleWebKit' || $browser['browser']!='OmniWeb') && $browser['browser'] != "Indeterminable" && $browser['browser'] != "Crawler/Search Engine" && $browser['version'] != "Indeterminable") {
			// Make sure we have at least .0 for a minor version for Safari and OmniWeb
			$browser['version'] = (!eregi('\.',$browser['version']))?$browser['version'].".0":$browser['version'];
			
			eregi('^([0-9]*).(.*)$',$browser['version'],$v);
			$browser['majorver'] = $v[1];
			$browser['minorver'] = $v[2];
			}
		if (empty($browser['version']) || $browser['version']=='.0') {
			$browser['version']		= "Indeterminable";
			$browser['majorver']	= "Indeterminable";
			$browser['minorver']	= "Indeterminable";
			}
		
		return $browser;
	}
	
	function determineLanguage() {
		$lang_choice = "empty"; 
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
			// Capture up to the first delimiter (, found in Safari)
			preg_match("/([^,;]*)/",$_SERVER["HTTP_ACCEPT_LANGUAGE"],$langs);
			$lang_choice = $langs[0];
		}
		return $lang_choice;
	}
	
	
	
	
	
	
	
	
	// DISPLAY
	
	function getKeywords() {
		global $wpdb;
		$query = "SELECT searchterms, count
				  FROM $this->table_search
				  ORDER BY count DESC
				  LIMIT 0,36";
		
		if ($results = $wpdb->get_results($query)) {
			$ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$ul .= "\t<tr><th>Search Strings</th><th class=\"last\">Hits</th></tr>\n";
			foreach( $results as $r ) {
				$ul .= "\t<tr><td>$r->searchterms</td><td class=\"last\">$r->count</td></tr>\n";
			}
			$ul .= "</table>";
		}
		return $ul;
	}
	
	function getReferers() {
		global $wpdb;
		
		$query = "SELECT referer, resource, dt 
				  FROM $this->table_stats
				  WHERE referer NOT LIKE '%".$this->trimReferer($_SERVER['SERVER_NAME'])."%' AND 
						referer!='' 
				  ORDER BY dt DESC 
				  LIMIT 0,36";
				  
		if ($results = $wpdb->get_results($query)) {
			$ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$ul .= "\t<tr><th>Recent Referrers</th><th class=\"last\">When</th></tr>\n";
			foreach( $results as $r ) {
				$url = parse_url($r->referer);
				
				$when = ($r->dt >= strtotime(date("j F Y",time())))?gmdate("g:i a",$r->dt+(((gmdate('I'))?($this->tz_offset+1):$this->tz_offset)*3600)):gmdate("M j",$r->dt+(((gmdate('I'))?($this->tz_offset+1):$this->tz_offset)*3600));
				
				$ul .= "\t<tr><td><a href=\"$r->referer\" title=\"$resource\" rel=\"nofollow\">".$this->trimReferer($url['host'])."</a></td><td class=\"last\">$when</td></tr>\n";
			}
			$ul .= "</table>";
		}
		return $ul;
	}
	
	function getDomains() {
		global $wpdb;
		
		$query = "SELECT domain, referer, resource, COUNT(domain) AS 'total' 
				  FROM $this->table_stats
				  WHERE domain !='".$this->trimReferer($_SERVER['SERVER_NAME'])."' AND 
						domain!='' 
				  GROUP BY domain 
				  ORDER BY total DESC, dt DESC";
		
		if ($results = $wpdb->get_results($query)) {
			$ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$ul .= "\t<tr><th>Repeat Referrers</th><th class=\"last\">Hits</th></tr>\n";
			$i=0;
			foreach( $results as $r ) {
				if ($i++ < 36) {
					$ul .= "\t<tr><td><a href=\"$r->referer\" title=\"$resource\" rel=\"nofollow\">$r->domain</a></td><td class=\"last\">$r->total</td></tr>\n";
				} else {
					break;
				}
			}
			$ul .= "</table>";
		}
		return $ul;
	}
	
	function getCountries() {
		global $wpdb;
		
		$query = "SELECT country, COUNT(country) AS 'total' 
				  FROM $this->table_stats
				  WHERE country!='' 
				  GROUP BY country 
				  ORDER BY total DESC";
		
		if ($results = $wpdb->get_results($query)) {
			$ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$ul .= "\t<tr><th>Country</th><th class=\"last\">Visits</th></tr>\n";
			$i=0;
			foreach( $results as $r ) {
				if ($i++ < 36) {
					$url = parse_url($r->referer);
					$ul .= "\t<tr><td>$r->country</td><td class=\"last\">$r->total</td></tr>\n";
				} else {
					break;
				}
			}
			$ul .= "</table>";
		}
		return $ul;
	}
	
	function getResources() {
		global $wpdb;
		
		$query = "SELECT resource, referer, COUNT(resource) AS 'requests' 
				  FROM $this->table_stats
				  WHERE 
				  resource NOT LIKE '%/inc/%' 
				  GROUP BY resource
				  ORDER BY requests DESC 
				  LIMIT 0,36";
		
		if ($results = $wpdb->get_results($query)) {
			$ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$ul .= "\t<tr><th>Resource</th><th class=\"last\">Requests</th></tr>\n";
			foreach( $results as $r ) {
				$resource = $this->truncate($r->resource,24);
				$referer = (!empty($r->referer))?$r->referer:'No referrer';
				$ul .= "\t<tr><td><a href=\"http://".$this->trimReferer($_SERVER['SERVER_NAME'])."$r->resource\" title=\"$referer\">".$resource."</a></td><td class=\"last\">$r->requests</td></tr>\n";
			}
			$ul .= "</table>";
		}
		return $ul;
	}
	
	
	function getPlatforms() {
		global $wpdb;
		
		$th = $this->getTotalHits();
		$query = "SELECT platform, COUNT(platform) AS 'total' 
				  FROM $this->table_stats
				  GROUP BY platform
				  ORDER BY total DESC";
		if ($results = $wpdb->get_results($query)) {
			$ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$ul .= "\t<tr><th>Platform</th><th class=\"last\">%</th></tr>\n";
			foreach( $results as $r ) {
				$ul .= "\t<tr><td>$r->platform</td><td class=\"last\">".number_format(($r->total/$th)*100)."%</td></tr>\n";
			}
			$ul .= "</table>";
		}
		return $ul;
	}
	
	function getBrowsers() {
		global $wpdb;
	
		$th = $this->getTotalHits();
		$query = "SELECT browser, version, COUNT(*) AS 'total' 
				  FROM $this->table_stats
				  WHERE browser != 'Indeterminable' 
				  GROUP BY browser, version
				  ORDER BY total DESC";
		if ($results = $wpdb->get_results($query)) {
			$ul  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$ul .= "\t<tr><th>Browser</th><th>Version</th><th class=\"last\">%</th></tr>\n";
			foreach( $results as $r ) {
				$p = number_format(($r->total/$th)*100);
				if ($p>=1) {
					$ul .= "\t<tr><td>$r->browser</td><td>$r->version</td><td class=\"last\">$p%</td></tr>\n";
				}
			}
			$ul .= "</table>";
		}
		return $ul;
	}
	
	function getTotalHits() {
		global $wpdb;
		$query = "SELECT COUNT(*) AS 'total' FROM $this->table_stats";
		return $wpdb->get_var($query);
	}
	function getFirstHit() {
		global $wpdb;
		$query = "SELECT dt FROM $this->table_stats ORDER BY dt ASC LIMIT 0,1";
		return $wpdb->get_var($query);
	}
	function getUniqueHits() {
		global $wpdb;
		$query = "SELECT COUNT(DISTINCT remote_ip) AS 'total' FROM $this->table_stats";
		return $wpdb->get_var($query);
	}
	function getTodaysHits() {
		global $wpdb;
		$dt = strtotime(gmdate("j F Y",time()+(((gmdate('I'))?($this->tz_offset+1):$this->tz_offset)*3600)));
		$dt = $dt-(3600*2); // The above is off by two hours. Don't know why yet...
		$query = "SELECT COUNT(*) AS 'total' FROM $this->table_stats WHERE dt >= $dt";
		return $wpdb->get_var($query);
	}
		
	function getTodaysUniqueHits() {
		global $wpdb;
		$dt = strtotime(gmdate("j F Y",time()+(((gmdate('I'))?($this->tz_offset+1):$this->tz_offset)*3600)));
		$dt = $dt-(3600*2); // The above is off by two hours. Don't know why yet...
		$query = "SELECT COUNT(DISTINCT remote_ip) AS 'total' FROM $this->table_stats WHERE dt >= $dt";
		return $wpdb->get_var($query);
	}
		
	function getWeeksHits() {
		global $wpdb;
		
		$dt = strtotime(gmdate("j F Y",time()+(((gmdate('I'))?($this->tz_offset+1):$this->tz_offset)*3600)));
		$dt = $dt-(3600*2); // The above is off by two hours. Don't know why yet...
		
		$tmp = "";
		$dt_start = time();
		
		$tmp  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
		$tmp .= "\t<tr><th colspan=\"2\">Hits in the last week</th></tr>\n";
		$tmp .= "\t<tr><td class=\"accent\">Day</td><td class=\"accent last\">Hits</td></tr>\n";
		
		for ($i=0; $i<7; $i++) {
			$dt_stop = $dt_start;
			$dt_start = $dt - ($i * 60 * 60 * 24);
			$day = ($i)?gmdate("l, j M Y",$dt_start):"Today, ".gmdate("j M Y",$dt_start);
			$query = "SELECT COUNT(*) AS 'total' FROM $this->table_stats WHERE dt > $dt_start AND dt <=$dt_stop";
			if ($total = $wpdb->get_var($query)) {
				$tmp .= "\t<tr><td>$day</td><td class=\"last\">$total</td></tr>\n";
			}
		}
		$tmp .= "</table>";
		return $tmp;
	}
	
	function getLanguage() {
		global $wpdb;
		
		$query = "SELECT COUNT(*) AS 'total' FROM $this->table_stats WHERE language != '' AND language != 'empty'";
		$th = $wpdb->get_var($query);
				
		$query = "SELECT language, COUNT(language) AS 'total' 
				  FROM $this->table_stats 
				  WHERE language != '' AND 
				  language != 'empty' 
				  GROUP BY language
				  ORDER BY total DESC";
				  
		if ($results = $wpdb->get_results($query)) {
			$html  = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
			$html .= "\t<tr><th>Language</th><th class=\"last\">%</th></tr>\n";
			foreach( $results as $r ) {
				$l = $r->language;
				$lang = (isset($this->languages[$l]))?$this->languages[$l]:$l;
				$per = number_format(($r->total/$th)*100);
				$per = ($per)?$per:'<1';
				$html .= "\t<tr><td>$lang</td><td class=\"last\">$per%</td></tr>\n";
				}
			$html .= "</table>";
			}
		return $html;
		}
	
	function truncate($var, $len = 120) {
		if (empty ($var)) return "";
		if (strlen ($var) < $len) return $var; 
		if (preg_match ("/(.{1,$len})\s./ms", $var, $match)) { 
			return $match [1] . "..."; 
		} else { 
			return substr ($var, 0, $len) . "..."; 
		}
	}
	
	function trimReferer($r) {
		$r = eregi_replace("http://","",$r);
		$r = eregi_replace("^www.","",$r);
		$r = $this->truncate($r,36);
		return $r;
	}
	
	
}

// Always want that instance
$wpss = new wp_shortstat();

// Tracking hook
add_action('shutdown', array(&$wpss, 'track'));

// Installation/Initialization Routine
if (isset($_GET['activate']) && $_GET['activate'] == 'true')
	add_action('init', array(&$wpss, 'setup'));





function wp_shortstat_display_stats() { // For ze admin page
	global $wpss;
	?>
	
<div class="wrap">
	<h2>ShortStat</h2>
	
	<div id="wp_shortstat">
	
	<div class="column">
		<div class="module waccents">
			<h3>Hits <span>Uniques</span></h3>
			<div><table border="0" cellspacing="0" cellpadding="0">
				<tr><th>Hits</th><th class="last">Uniques</th></tr>
				<tr><td colspan="2" class="accent">Since <?php echo gmdate("g:i a j M Y",$wpss->getFirstHit()+(((gmdate('I'))?($wpss->$tz_offset+1):$wpss->$tz_offset)*3600));?></td></tr>
				<tr><td><?php echo $wpss->getTotalHits(); ?></td><td class="last"><?php echo $wpss->getUniqueHits(); ?></td></tr>
				<tr><td colspan="2" class="accent">Just Today as of <?php echo gmdate("g:i a j M Y",time()+(((gmdate('I'))?($wpss->tz_offset+1):$wpss->tz_offset)*3600));?></td></tr>
				<tr><td><?php echo $wpss->getTodaysHits(); ?></td><td class="last"><?php echo $wpss->getTodaysUniqueHits(); ?></td></tr>
			</table></div>
		</div>
		
		<div class="module waccents">
			<h3>Hits in the last week</h3>
			<div><?php echo $wpss->getWeeksHits(); ?></div>
		</div>
		
		<div class="module">
			<h3>Platform <span>%</span></h3>
			<div><?php echo $wpss->getPlatforms(); ?></div>
		</div>
	</div> <!-- CLOSE COLUMN -->
	
	<div class="module">
		<h3>Browser <span>%</span></h3>
		<div><?php echo $wpss->getBrowsers(); ?></div>
	</div>
	
	<div class="module">
		<h3>Recent Referrers <span>When</span></h3>
		<div><?php echo $wpss->getReferers(); ?></div>
	</div>
	
	<div class="module">
		<h3>Repeat Referrers <span>Hits</span></h3>
		<div><?php echo $wpss->getDomains(); ?></div>
	</div>
	
	<div class="module">
		<h3>Resources <span>Hits</span></h3>
		<div><?php echo $wpss->getResources(); ?></div>
	</div>
	
	<div class="module">
		<h3>Search Strings <span>Hits</span></h3>
		<div><?php echo $wpss->getKeywords(); ?></div>
	</div>
	
	<div class="module">
		<h3>Countries <span>Visits</span></h3>
		<div><?php echo $wpss->getCountries(); ?></div>
	</div>
	
	<div class="module">
		<h3>Languages <span>%</span></h3>
		<div><?php echo $wpss->getLanguage(); ?></div>
	</div>
	
	<div id="donotremove">&copy; 2004 <a href="http://www.shauninman.com/">Shaun Inman</a></div>

	</div>
	
</div>
	
	<?php
}


function wp_shortstat_add_pages($s) {
	add_submenu_page('index.php', 'ShortStat', 'ShortStat', 1, __FILE__, 'wp_shortstat_display_stats');
	return $s;
}
add_action('admin_menu', 'wp_shortstat_add_pages');


function wp_shortstat_css() {
	?>
	<style type="text/css">
/* BASIC STYLES
------------------------------------------------------------------------------*/
#wp_shortstat, #wp_shortstat table, #wp_shortstat td, #wp_shortstat th { font: 10px/14px Verdana, sans-serif; color: #333; }
#wp_shortstat a { color: #AB6666; text-decoration: none; border: 0;}
#wp_shortstat a:visited { /*color: #666;*/ }
#wp_shortstat a:hover { color: #710101; text-decoration: none; }

/* MODULE STYLES
------------------------------------------------------------------------------*/
#wp_shortstat .module {
	float: left; 
	width: 272px;
	margin: 0 3px 3px 0; 
	border-top: 1px solid #333; 
	border-bottom: 1px solid #888; 
	padding-bottom: 1px;
	font: 10px/14px Verdana, sans-serif;
	}
#wp_shortstat .module h3 {
	position: relative;
	margin: 0 0 1px;
	padding: 12px 5px 1px 4px;
	text-align: left;
	font-size: 10px;
	font-weight: normal;
	color: #FFF; 
	background-color: #666;
	text-shadow: 2px 2px #555;
	border-top: 8px solid #555;
	border-bottom: 1px solid #555;
	}
#wp_shortstat .module h3 span {
	position: absolute;
	right: 19px;
	}
#wp_shortstat .module div {
	width: 272px;
	height: 253px;
	overflow: auto;
	}
#wp_shortstat .module div table {
	width: 256px;
	border-bottom: 1px dotted #CCC;
	}
#wp_shortstat .module div table th {
	display: none;
	}
#wp_shortstat .module div table td {
	padding: 3px 16px 3px 3px; 
	border-top: 1px dotted #CCC; 
	vertical-align: top;
	}
#wp_shortstat .module div table td.last { 
	text-align: right;
	padding-right: 2px;
	}

#wp_shortstat .waccents h3 {
	margin-bottom: 0;
	}
#wp_shortstat .waccents div table {
	border-bottom-width: 0;
	}
#wp_shortstat .waccents div table td {
	border-top-width: 0;
	border-bottom: 1px dotted #CCC;  
	}


#wp_shortstat .module div table td.accent { 
	font-size: 9px; 
	color: #555; 
	background-color: #CCC; 
	border-top: 1px solid #FFF;
	border-bottom: 1px solid #BBB;
	text-shadow: 2px 2px #DDD;
	margin-bottom: 1px;
	}

#wp_shortstat .column {
	font: 10px/14px Verdana, sans-serif;
	width: 272px;
	height: 606px;
	float: left;
	margin: 0 3px 3px 0; 
	}
#wp_shortstat .column .module {
	margin: 0 0 12px 0;
	float: none;
	} 
#wp_shortstat .column .module h3 span {
	right: 5px;
	}
#wp_shortstat .column .module div {
	width: auto;
	height: auto;
	}
#wp_shortstat .column .module div table {
	width: 272px;
	}


/* H STYLES
------------------------------------------------------------------------------*/
#wp_shortstat h1 {
	font: normal 18px/18px Helvetica, Arial;
	color: #999;
	clear: both;
	margin: 0;
	}
#wp_shortstat h1 em {
	color: #710101;
	font-style: normal;
	}
#wp_shortstat h2 {
	font: normal 10px/14px Geneva;
	color: #999;
	clear: both;
	margin: 0 0 16px;
	padding: 0 0 0 2px;
	}

/* COPYRIGHT STYLES
------------------------------------------------------------------------------*/
#wp_shortstat #donotremove { display: block; clear: both; }
	</style>
	<?php
}


add_action('admin_head', 'wp_shortstat_css');

?>