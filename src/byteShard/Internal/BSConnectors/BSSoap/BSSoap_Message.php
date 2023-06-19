<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

use SimpleXMLElement;

class BSSoap_Message
{
    private $bespinSOAP_header;
    private $bespinSOAP_body;
    private $BSSoap_Header;
    private $BSSoap_Body;
    private $messageID;
    private $xml_version = '1.0';
    private $xml_encoding;
    private $namespace;

    public function __construct(BSSoap_Body $bespinSOAP_body, BSSoap_Header $bespinSOAP_header = null, $encoding = null, $xmlVersion = null)
    {
        $this->BSSoap_Body   = $bespinSOAP_body;
        $this->BSSoap_Header = $bespinSOAP_header;
        if ($bespinSOAP_header !== null) {
            $this->bespinSOAP_header = $bespinSOAP_header;
        }
        $this->bespinSOAP_body = $bespinSOAP_body;
        if ($encoding !== null) {
            $this->xml_encoding = $encoding;
        }
        if ($xmlVersion !== null) {
            $this->xml_version = $xmlVersion;
        }
        $this->namespace['soap'] = 'http://schemas.xmlsoap.org/soap/envelope/';
        $this->namespace['xsi']  = 'http://www.w3.org/2001/XMLSchema-instance';
        $this->namespace['xsd']  = 'http://www.w3.org/2001/XMLSchema';
        $prefix                  = $this->bespinSOAP_body->getPrefix();
        if ($prefix !== '' && $prefix !== null) {
            $this->namespace[$this->bespinSOAP_body->getPrefix()] = $this->bespinSOAP_body->getNamespace();
        }
    }

    /**
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->xml_encoding = $encoding;
    }

    public function getEncoding()
    {
        return $this->xml_encoding;
    }

    public function getMessageID()
    {
        return $this->messageID;
    }

    public function getAction()
    {
        return $this->bespinSOAP_body->getAction();
    }

    public function getMessage()
    {
        $message = new SimpleXMLElement('<?xml version="'.$this->xml_version.'" encoding="'.$this->xml_encoding.'" ?><soap:Envelope/>');
        if (is_array($this->namespace)) {
            foreach ($this->namespace as $prefix => $namespace) {
                $message->addAttribute('xmlns:xmlns:'.$prefix, $namespace);
            }
        }
        if (!empty($this->bespinSOAP_header)) {
            $this->bespinSOAP_header->getHeader($message);
            //$message .= $this->bespinSOAP_header->getHeader();
        }
        if (!empty($this->bespinSOAP_body)) {
            $this->bespinSOAP_body->getBody($message);
            //$message .= $this->bespinSOAP_body->getBody();
        }
        return $message->asXML();
    }

    public function generateMessageID()
    {
        $tmp        = '';
        $parameters = $this->bespinSOAP_body->getParameters();
        if (!empty($parameters)) {
            if (is_array($parameters)) {
                foreach ($parameters as $key => $val) {
                    $tmp .= $key.$val;
                }
            } else {
                $tmp = $parameters;
            }
        }
        $this->messageID = md5($this->bespinSOAP_body->getAction().$tmp.date('YmdHis'));
        if (!empty($this->bespinSOAP_header)) {
            $this->bespinSOAP_header->setMessageID($this->messageID);
        }
    }

    public function checkMessageID(&$curl_response)
    {
        $result = null;
        if ($this->bespinSOAP_header instanceof BSSoap_Header) {
            $header_message_id = $this->bespinSOAP_header->getMessageID();
            if ($header_message_id !== null) {
                $startPos  = stripos($curl_response, '<wsa:messageid>') + 15;
                $endPos    = stripos($curl_response, '</wsa:messageid>', $startPos);
                $messageID = substr($curl_response, $startPos, $endPos - $startPos);
                if ($header_message_id !== $messageID) {
                    $result = false;
                } else {
                    $result = true;
                }
            }
        }
        return $result;
    }

    public function getWSSEObject(): ?BSSoap_wsse
    {
        if (!empty($this->bespinSOAP_header)) {
            return $this->bespinSOAP_header->getWSSEObject();
        } else {
            return null;
        }
    }
}
