<?php

class Pingback_Utility
{
  const REGEXP_PINGBACK_LINK = '<link rel="pingback" href="([^"]+)" ?/?>';

  public static function isURL($url) {
  	return filter_var($url, FILTER_VALIDATE_URL);
  }

  public static function isPingbackEnabled($url) {
    return (bool)self::getPingbackServerURL($url);
  }
  
  public static function getRawPostData() {
    return file_get_contents('php://input');
  }

  public static function getPingbackServerURL($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $headers = http_parse_headers(curl_exec($curl));
    curl_close($curl);

    if(isset($headers['X-Pingback']) && !empty($headers['X-Pingback'])) {
      return $headers['X-Pingback'];
    }

    $response = file_get_contents($url);

    return preg_match(self::REGEXP_PINGBACK_LINK, $response, $match) ? $match[1] : false;
  }

  public static function isBacklinking($from, $to) {
    $content = file_get_contents($from);
    if($content !== false) {
      $doc = new DOMDocument();
      $doc->loadHTML($content);
      foreach($doc->getElementsByTagName('a') as $link) {
        if($link->getAttribute('href') == $to) {
          return true;
        }
      }
    }
    return false;
  }

  public static function sendPingback($from, $to, $server) {
    $request = xmlrpc_encode_request('pingback.ping', array($from,  $to));
    $curl = curl_init($server);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
  }
}

if(!function_exists('http_parse_headers'))
{
    function http_parse_headers( $header )
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }
}

