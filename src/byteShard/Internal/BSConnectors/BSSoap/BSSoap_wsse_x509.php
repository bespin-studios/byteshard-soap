<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

class BSSoap_wsse_x509 extends BSSoap_wsse
{
    const encodingType_Base64Binary = 'Base64Binary';

    private $encodingType;
    private $valueType;

    public function __construct()
    {
        parent::__construct();
    }

    public function setEncodingType($encodingType)
    {
        $this->encodingType = $encodingType;
    }

    public function setValueType($valueType)
    {
        $this->valueType = $valueType;
    }

    public function setX509Token($token)
    {
        $this->token = $token;
        if ($this->tokenFrom === null) {
            $this->tokenFrom = BSSoap_wsse::tokenFromWSSETokenProperty;
        }
    }

    public function getWSSE()
    {
        return '<'.$this->prefix.':BinarySecurityToken '.((!empty($this->encodingType)) ? 'EncodingType="'.$this->namespace.'#'.$this->encodingType.'" ' : '').((!empty($this->valueType)) ? 'ValueType="'.$this->valueType.'" ' : '').'xmlns:'.$this->prefix.'="'.$this->namespace.'">'.$this->token.'</'.$this->prefix.':BinarySecurityToken>';
    }
}
