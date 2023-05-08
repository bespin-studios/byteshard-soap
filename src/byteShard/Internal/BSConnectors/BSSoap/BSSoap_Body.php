<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

use SimpleXMLElement;

class BSSoap_Body
{
    private $action;
    private $parameters;
    private $namespace;
    private $prefix = '';

    public function __construct($action, $parameters, $namespace, $prefix)
    {
        $this->action     = $action;
        $this->parameters = $parameters;
        $this->namespace  = $namespace;
        if ($prefix !== null && $prefix !== '') {
            $this->prefix = rtrim($prefix, ':').':';
        }
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getBody(SimpleXMLElement &$parent)
    {
        $body   = $parent->addChild('xmlns:soap:Body');
        $action = $body->addChild($this->action, null, $this->namespace);
        if (is_array($this->parameters) && count($this->parameters) > 0) {
            $this->addElementToBody($action, $this->parameters);
        }
    }

    private function addElementToBody(SimpleXMLElement &$body, $elements)
    {
        foreach ($elements as $attribute => $value) {
            if (is_array($value)) {
                $child = $body->addChild('xmlns:'.$this->prefix.$attribute);
                $this->addElementToBody($child, $value);
            } else {
                if ($value === null) {
                    $child = $body->addChild('xmlns:'.$this->prefix.$attribute);
                    $child->addAttribute('xmlns:xsi:nil', 'true');
                } else {
                    $body->addChild('xmlns:'.$this->prefix.$attribute, $value);
                }
            }
        }
    }
}
