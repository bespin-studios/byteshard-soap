<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

use SimpleXMLElement;

class BSSoap_wsa
{
    private $namespace = 'http://www.w3.org/2005/08/addressing';
    private $prefix    = 'wsa:';
    //private $messageID;
    //private $fromAddress;
    //private $action;
    private $addresses = array();
    private $messageID;
    private $fromAddress;
    private $action;

    public function __construct()
    {
    }

    public function getNamespace(SimpleXMLElement &$header)
    {
        $header->addAttribute('xmlns:xmlns:'.rtrim($this->prefix, ':'), $this->namespace);
    }

    public function addAddress(array $address)
    {
        $this->addresses = array_merge_recursive($this->addresses, $address);
    }

    public function getElements(SimpleXMLElement &$header)
    {
        $this->addElementToHeader($header, $this->addresses);
    }

    private function addElementToHeader(SimpleXMLElement &$header, $elements)
    {
        foreach ($elements as $attribute => $value) {
            if (is_array($value)) {
                $child = $header->addChild('xmlns:'.$this->prefix.$attribute);
                $this->addElementToHeader($child, $value);
            } else {
                $header->addChild('xmlns:'.$this->prefix.$attribute, $value);
            }
        }
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setMessageID($messageID)
    {
        $this->messageID = $messageID;
    }

    public function setFromAddress($address)
    {
        $this->fromAddress = $address;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getWSA()
    {
        $wsa = '';
        if ($this->prefix === '' || $this->prefix === null) {
            $prefix = '';
            $xmlns  = 'xmlns';
        } else {
            $prefix = $this->prefix.':';
            $xmlns  = 'xmlns:'.$this->prefix;
        }
        if (!empty($this->messageID)) {
            $wsa .= '<'.$prefix.'MessageID '.$xmlns.'="'.$this->namespace.'">'.$this->messageID.'</'.$prefix.'MessageID>';
        }
        if (!empty($this->fromAddress)) {
            $wsa .= '<'.$prefix.'From '.$xmlns.'="'.$this->namespace.'"><'.$prefix.'Address '.$xmlns.'="'.$this->namespace.'">'.$this->fromAddress.'</'.$prefix.'Address></'.$prefix.'From>';
        }
        if (!empty($this->action)) {
            $wsa .= '<'.$prefix.'Action '.$xmlns.'="'.$this->namespace.'">'.$this->action.'</'.$prefix.'Action>';
        }
        return $wsa;
    }
}
