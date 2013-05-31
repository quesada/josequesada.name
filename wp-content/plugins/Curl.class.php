<?php



class Curl {

	var $curl;

    var $manual_follow;

    var $redirect_url;

	var $cookiefile;

	var $used;

	var $authinfo;

	var $setred;



	function Curl() {

		if(!$this->cookiefile){

			$this->setred = true;

			$this->cookiefile = tempnam("tmp","COO");

		}

		$this->used++;

		

		$this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)');

		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST,false);

		curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);

		curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookiefile);

		curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookiefile);

        curl_setopt($this->curl, CURLOPT_HEADER, true);

	

        //$this->setRedirect();



		



	}



	function getInstance() {

		static $instance;

		if (!isset($instance)) {

			$curl =& new Curl;

			$instance = array($curl);

		}

		return $instance[0];

	}



    function setTimeout($connect, $transfer) {

        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $connect);

        curl_setopt($this->curl, CURLOPT_TIMEOUT, $transfer);

    }



    function getError() {

        return curl_errno($this->curl) ? curl_error($this->curl) : false;

    }



    function disableRedirect() {

        $this->setRedirect(false);

    }



    function setRedirect($enable = true) {

		$this->setred = $enable;

	}



    function getHttpCode() {

        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

    }



	function auth($user, $pass) {

		$this->authinfo = "$user:$pass";

		

	}



	function makeQuery($data) { 

		if (is_array($data)) {

			$fields = array();

			foreach ($data as $key => $value) {

 				$fields[] = $key . '=' . urlencode($value);

			}

			$fields = implode('&', $fields);

		} else {

			$fields = $data;

		}



		return $fields;

	}

	

    // FOLLOWLOCATION manually if we need to

	function maybeFollow($page) {

        if (strpos($page, "\r\n\r\n") !== false) {

            list($headers, $page) = explode("\r\n\r\n", $page, 2);

        }



        $code = $this->getHttpCode();

        if ($code > 300 && $code < 310) {

            preg_match("#Location: ?(.*)#i", $headers, $match);

            $this->redirect_url = trim($match[1]);



	        if ($this->manual_follow) {

                return $this->get($this->redirect_url);

            }

        } else {

            $this->redirect_url = '';

        }

            

	    return $page;

	}

	

	function post($url, $data , $ref = '') {

		if($this->used > 0){

			$this->Curl();

		}



		$fields = $this->makeQuery($data);

		

		curl_setopt($this->curl, CURLOPT_URL, $url);

		curl_setopt($this->curl, CURLOPT_POST, true);

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);

		curl_setopt($this->curl, CURLOPT_HEADER, true);

		

		if($ref){

			curl_setopt($this->curl, CURLOPT_REFERER, $ref);

		}



		if ($this->setred) {

	        $this->manual_follow = !@curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);

        } else {

	        @curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);

            $this->manual_follow = false;

        }

		

		if($this->authinfo) curl_setopt($this->curl, CURLOPT_USERPWD, $this->authinfo);



		$page = curl_exec($this->curl);

			

		$error = curl_errno($this->curl);	

		if ($error != CURLE_OK || empty($page)) {

			return false;

		}



		//curl_setopt($this->curl, CURLOPT_POST, false);

		//curl_setopt($this->curl, CURLOPT_POSTFIELDS, '');

		

		return $this->maybeFollow($page);

	}

	

	function get($url, $data = null) {

		if($this->used > 0){

			$this->Curl();

		}



        if (!is_null($data)) {

            $fields = $this->makeQuery($data);

            $url .= '?' . $fields;

        }



		curl_setopt($this->curl, CURLOPT_URL, $url);

		if($this->authinfo) curl_setopt($this->curl, CURLOPT_USERPWD, $this->authinfo);

		if ($this->setred) {

	        $this->manual_follow = !@curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);

        } else {

	        @curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);

            $this->manual_follow = false;

        }



		$page = curl_exec($this->curl);

		

		$error = curl_errno($this->curl);



		if ($error != CURLE_OK || empty($page)) {

			return false;

		}

		

		return $this->maybeFollow($page);

	}

}



?>

