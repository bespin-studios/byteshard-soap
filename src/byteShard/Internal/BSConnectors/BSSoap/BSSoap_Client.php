<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

use DOMDocument;
use http\Cookie;

class BSSoap_Client
{
    private $BSSoap_message;
    private $BSSoap_wsse;
    private $bespinSOAP_result;

    private $cookieObject   = null;
    private $http_cookie;
    private $http_useragent = 'BSSoap/1.0.0';
    private $encoding       = 'ISO-8859-1';

    private $wsseToken;

    private $curl_endpoint;
    private $curl_handler;
    private $curl_http_code;
    private $curl_errno;
    private $curl_response;
    private $curl_response_info;
    private $curl_maxRecalls = 0;
    private $curl_timeout    = 600;

    private $soap_response_http_header;
    private $soap_response_xml;
    private $soap_response_xml_woNS;
    private $http_header;

    public function __construct($endpoint, BSSoap_Message $BSSoap_message)
    {
        ini_set('memory_limit', -1);
        $this->curl_endpoint  = $endpoint;
        $this->BSSoap_message = $BSSoap_message;
        $this->BSSoap_wsse    = $this->BSSoap_message->getWSSEObject();
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    public function setNumberOfRetries($int)
    {
        $this->curl_maxRecalls = $int;
    }

    public function call($resultName = null)
    {
        $this->callSoapServer();
        if ($this->curl_response !== false) {
            $this->parseSoapResponse();
            $result_index = ($resultName !== null) ? $resultName : $this->BSSoap_message->getAction().'Result';
            if (isset($this->bespinSOAP_result['Body'])) {
                if (isset($this->bespinSOAP_result['Body'][$result_index])) {
                    return $this->bespinSOAP_result['Body'][$result_index];
                } else {
                    return $this->bespinSOAP_result['Body'];
                }
            } else {
                return $this->bespinSOAP_result;
            }
        } else {
            return false;
        }
    }

    public function setCookie($cookie)
    {
        $this->http_cookie = $cookie;
    }

    public function setCookieObject(cookie $cookieObject)
    {
        if (($cookieObject instanceof cookie) && method_exists($cookieObject, 'getCookieString') === true) {
            $this->cookieObject = $cookieObject;
        }
    }

    public function setHTTPHeader($headerArray)
    {
        $this->http_header = $headerArray;
    }

    private function getHTTPHeader()
    {
        $http_header[] = 'User-Agent: '.$this->http_useragent;
        $http_header[] = 'Content-Type: text/xml; charset='.$this->encoding;
        $http_header[] = 'Connection: Keep-Alive';
        $http_header[] = 'Keep-Alive: '.($this->curl_timeout - 1);
        $http_header[] = 'Expect:';
        $http_header[] = 'SOAPAction: "http://ws.cdyne.com/ResolveIP"';
        if ($this->cookieObject !== null) {
            $http_header[] = $this->cookieObject->getCookieString();
        }
        if (strlen($this->http_cookie) > 0) {
            $http_header[] = $this->http_cookie;
        }
        return $http_header;
    }

    private function callSoapServer()
    {
        // generate message ID for this request
        $this->BSSoap_message->generateMessageID();
        for ($retry = 0; $retry <= $this->curl_maxRecalls; $retry++) {
            $this->curl_response      = null;
            $this->curl_response_info = null;

            // call soap server
            $this->curl_exec();

            // no error and response ID matches request ID
            if ($this->curl_http_code !== null && $this->curl_http_code === 200) {
                // check if response messageID matches request messageID
                $responseIDMatchesRequestID = $this->checkMessageID();
                if ($responseIDMatchesRequestID === null || $responseIDMatchesRequestID === true) {
                    break;
                }
            }

            // an error occured, create log entry and continue loop until max nr of retries
            if ($this->curl_http_code !== null && $this->curl_http_code === 200 && !$responseIDMatchesRequestID) {
                //TODO: Log 'Webservice call "'.$this->bespinSOAP_message->getAction().'" '.$this->getRetryString($retry).' try failed. Wrong Message returned.'
            } else {
                //TODO: Log 'Webservice call "'.$this->bespinSOAP_message->getAction().'" '.$this->getRetryString($retry).' try failed. HTTP Code: '.$this->curl_http_code.'. Curl ErrNo: '.$this->curl_errno
            }
            $this->curl_response = false;
            sleep(1);
        }
        if ($this->curl_response === false) {
            //TODO: Log 'Webservice call "'.$this->bespinSOAP_message->getAction().'" failed'
        } else {
            //TODO: Log 'Webservice call "'.$this->bespinSOAP_message->getAction().'" successful'
        }
    }

    private function curl_exec()
    {
        $message_encoding = $this->BSSoap_message->getEncoding();
        if ($message_encoding === null) {
            $this->BSSoap_message->setEncoding($this->encoding);
        }

        // generate a cookie for this request if needed
        if ($this->cookieObject !== null) {
            $this->cookieObject->fetchCookie();
        }

        // set wsse token for this request if needed
        if ($this->BSSoap_wsse instanceof BSSoap_wsse_x509) {
            if ($this->cookieObject !== null && $this->BSSoap_wsse->getTokenFrom() === BSSoap_wsse::tokenFromClientCookieObject) {
                $this->BSSoap_wsse->setX509Token($this->cookieObject->getToken());
            } elseif ($this->BSSoap_wsse->getTokenFrom() === BSSoap_wsse::tokenFromClientTokenProperty) {
                $this->BSSoap_wsse->setX509Token($this->wsseToken);
            }
        }

        // make sure to get a new curl connection
        if ($this->curl_handler !== null) {
            curl_close($this->curl_handler);
            $this->curl_handler = null;
        }

        $this->curl_handler = curl_init();
        curl_setopt($this->curl_handler, CURLOPT_URL, $this->curl_endpoint);
        curl_setopt($this->curl_handler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curl_handler, CURLOPT_HEADER, true);
        curl_setopt($this->curl_handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl_handler, CURLOPT_TIMEOUT, $this->curl_timeout);
        curl_setopt($this->curl_handler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl_handler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl_handler, CURLOPT_POST, true);
        curl_setopt($this->curl_handler, CURLOPT_HTTPHEADER, $this->getHTTPHeader());
        curl_setopt($this->curl_handler, CURLOPT_POSTFIELDS, $this->BSSoap_message->getMessage());
        curl_setopt($this->curl_handler, CURLOPT_AUTOREFERER, true);
        curl_setopt($this->curl_handler, CURLOPT_COOKIESESSION, true);
        curl_setopt($this->curl_handler, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->curl_handler, CURLOPT_FORBID_REUSE, true);
        $this->curl_response  = curl_exec($this->curl_handler);
        $this->curl_errno     = curl_errno($this->curl_handler);
        $this->curl_http_code = curl_getinfo($this->curl_handler, CURLINFO_HTTP_CODE);
        //TODO: Log 'Request HTTP Header: '
        //TODO: Log $this->getHTTPHeader()
        //print "Header:<br>\n";
        //print $this->getHTTPHeader();
        //print "Body:<br>\n";
        //TODO: Log 'Request Body: '
        //TODO: Log $this->bespinSOAP_message->getMessage()
        //print $this->bespinSOAP_message->getMessage();
        //TODO: Log 'Curl Info: '
        //TODO: Log curl_getinfo($this->curl_handler)
        curl_close($this->curl_handler);
        $this->curl_handler = null;
        if ($message_encoding === null) {
            $this->BSSoap_message->setEncoding(null);
        }
    }

    private function checkMessageID()
    {
        $correctMessageID = null;
        if ($this->curl_http_code === 200) {
            return $this->BSSoap_message->checkMessageID($this->curl_response);
        }
        return $correctMessageID;
    }

    private function getRetryString($retry)
    {
        $retryString = $retry.'th';
        if ((($retry % 100) < 11) || (($retry % 100) > 13)) {
            $mod = $retry % 10;
            if ($mod === 1) {
                $retryString = $retry.'st';
            } elseif ($mod === 2) {
                $retryString = $retry.'nd';
            } elseif ($mod === 3) {
                $retryString = $retry.'rd';
            }
        }
        return $retryString;
    }

    private function parseSoapResponse()
    {
        $pos                             = strpos($this->curl_response, '<?xml');
        $this->soap_response_http_header = substr($this->curl_response, 0, $pos - 1);
        $this->soap_response_xml         = substr($this->curl_response, $pos);
        unset($this->curl_response);
        $this->removeNS();
        $doc = new DOMDocument();
        $doc->loadXML($this->soap_response_xml_woNS);
        $this->bespinSOAP_result = $this->dom_to_array($doc->documentElement);
        unset($doc);
    }

    private function removeNS()
    {
        $countNS = substr_count($this->soap_response_xml, 'xmlns');
        $pos     = 0;
        for ($i = 0; $i < $countNS; $i++) {
            $from                                                          = strpos($this->soap_response_xml, 'xmlns', $pos) + 6;
            $to                                                            = strpos($this->soap_response_xml, '=', $from);
            $nsOpen[substr($this->soap_response_xml, $from, $to - $from)]  = '<'.substr($this->soap_response_xml, $from, $to - $from).':';
            $nsClose[substr($this->soap_response_xml, $from, $to - $from)] = '</'.substr($this->soap_response_xml, $from, $to - $from).':';
            $pos                                                           = $from;
        }
        $this->soap_response_xml_woNS = str_replace($nsOpen, '<', $this->soap_response_xml);
        $this->soap_response_xml_woNS = str_replace($nsClose, '</', $this->soap_response_xml_woNS);
    }

    private function dom_to_array($node)
    {
        $result = array();
        if ($node->nodeType === 1) {
            if ($node->childNodes->length) {
                foreach ($node->childNodes as $child) {
                    if ($child->nodeType === 1) {
                        if (isset($child->tagName)) {
                            $val = $this->dom_to_array($child);
                            if ($child->attributes->length && !is_array($val)) {
                                foreach ($child->attributes as $attrName => $attrNode) {
                                    $tmp                = $val;
                                    $val                = array();
                                    $val['!'.$attrName] = $attrNode->value;
                                    $val['!']           = $tmp;
                                }
                            }
                            if (empty($val) && $val !== '0' && $val !== '') {
                                $val = '';
                            }
                            $result[$child->tagName][] = $val;
                        }
                    } elseif ($child->nodeType === 3 || $child->nodeType === 4) {
                        $val = utf8_decode($child->textContent);
                        if ($val || $val === '0') {
                            $result = (string)$val;
                        }
                    } // CHECK: Maybe an "else" is needed
                }
            }
            if (is_array($result)) {
                if ($node->attributes->length) {
                    foreach ($node->attributes as $attrName => $attrNode) {
                        if ($attrName !== 'nil') {
                            $result['!'.$attrName] = (string)$attrNode->value;
                        }
                    }
                }
                foreach ($result as $tag => $val) {
                    if (is_array($val) && count($val) === 1) {
                        $result[$tag] = $val[0];
                    }
                }
            }
        } elseif ($node->nodeType === 3 || $node->nodeType === 4) {
            $result = utf8_decode($node->textContent);
        } // CHECK: Maybe an "else" is needed
        return $result;
    }
}
