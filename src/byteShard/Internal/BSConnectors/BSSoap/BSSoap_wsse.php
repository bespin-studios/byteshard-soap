<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

use SimpleXMLElement;

abstract class BSSoap_wsse
{
    const tokenFromWSSETokenProperty   = 'tokenWSSEProperty';
    const tokenFromClientTokenProperty = 'tokenClientProperty';
    const tokenFromClientCookieObject  = 'cookieObject';

    protected $namespace = 'http://schemas.xmlsoap.org/ws/2002/07/secext';
    protected $prefix    = 'wsse';
    protected $tokenFrom = null;
    protected $token;

    public function __construct()
    {
    }

    public function getNamespace(SimpleXMLElement &$header)
    {
        $header->addAttribute('xmlns:xmlns:'.rtrim($this->prefix, ':'), $this->namespace);
    }

    public function getElements(SimpleXMLElement &$header)
    {
        //$this->addElementToHeader($header, $this->addresses);
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    abstract public function getWSSE();

    public function setTokenFrom($enum)
    {
        $this->tokenFrom = $enum;
    }

    public function getTokenFrom()
    {
        return $this->tokenFrom;
    }
}
