<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

use SimpleXMLElement;

class BSSoap_Header
{
    private              $wsa;
    private ?BSSoap_wsse $wsse      = null;
    private              $messageID = null;
    private string       $prefix    = '';

    public function __construct()
    {
    }

    public function setWSSE(BSSoap_wsse $wsseClassObject): void
    {
        $this->wsse = $wsseClassObject;
    }

    public function setWSA(BSSoap_wsa $wsaClassObject)
    {
        $this->wsa = $wsaClassObject;
        if ($this->messageID !== null) {
            $this->wsa->setMessageID($this->messageID);
        }
    }

    public function setMessageID($messageID)
    {
        if ($this->wsa instanceof BSSoap_wsa) {
            $this->wsa->setMessageID($messageID);
        }
        $this->messageID = $messageID;
    }

    public function getMessageID()
    {
        if ($this->wsa instanceof BSSoap_wsa) {
            return $this->messageID;
        } else {
            return null;
        }
    }

    public function getHeader(SimpleXMLElement &$parent)
    {
        if (($this->wsa instanceof BSSoap_wsa) || ($this->wsse instanceof BSSoap_wsse)) {
            $header = $parent->addChild('xmlns:soap:Header');
            if ($this->wsa instanceof BSSoap_wsa) {
                $this->wsa->getNamespace($header);
                $this->wsa->getElements($header);
            }
            if ($this->wsse instanceof BSSoap_wsse) {
                $this->wsse->getNamespace($header);
                $this->wsse->getElements($header);
            }
        }
        /*
                $headerNS = '';
                if ($this->wsse instanceof BSSoap_wsse) {
                    $wsse_prefix = $this->wsse->getPrefix();
                    if ($wsse_prefix === null || $wsse_prefix === '') {
                        $headerNS .= ' xmlns'.'="'.$this->wsse->getNamespace().'"';
                    } else {
                        $headerNS .= ' xmlns:'.$wsse_prefix.'="'.$this->wsse->getNamespace().'"';
                    }
                }
                if ($this->wsa instanceof BSSoap_wsa) {
                    $wsa_prefix = $this->wsa->getPrefix();
                    if ($wsa_prefix === null || $wsa_prefix === '') {
                        $headerNS .= ' xmlns'.'="'.$this->wsa->getNamespace().'"';
                    } else {
                        $headerNS .= ' xmlns:'.$wsa_prefix.'="'.$this->wsa->getNamespace().'"';
                    }
                }
                $header  = '<soap:Header'.(($headerNS != '') ? $headerNS : '').'>';
                if ($this->wsse instanceof BSSoap_wsse) {
                    $header .= $this->wsse->getWSSE();
                }
                if ($this->wsa instanceof BSSoap_wsa) {
                    $header .= $this->wsa->getWSA();
                }
                $header .= '</soap:Header>';
                return $header;*/
    }

    public function getWSSEObject(): ?BSSoap_wsse
    {
        if (!empty($this->wsse)) {
            return $this->wsse;
        } else {
            return null;
        }
    }

    private function addElementToHeader(SimpleXMLElement &$header, $elements)
    {
        foreach ($elements as $attribute => $value) {
            if (is_array($value)) {
                $child = $header->addChild('xmlns:'.$this->prefix.$attribute);
                $this->addElementToHeader($child, $value);
            } else {
                if ($value === null) {
                    $child = $header->addChild('xmlns:'.$this->prefix.$attribute);
                    $child->addAttribute('xmlns:xsi:nil', 'true');
                } else {
                    $header->addChild('xmlns:'.$this->prefix.$attribute, $value);
                }
            }
        }
    }
}
