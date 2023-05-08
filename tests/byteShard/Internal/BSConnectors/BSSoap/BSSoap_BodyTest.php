<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class BSSoap_BodyTest extends TestCase
{

    private $bsSoapBody;

    protected function setUp(): void
    {
        $this->bsSoapBody = new BSSoap_Body('test', 'test', 'test', 'test');
    }

    public function testGetBody()
    {
        $parent = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><soap:Envelope/>');
        $this->bsSoapBody->getBody($parent);
        $expectedResult = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope><soap:Body><test xmlns="test"/></soap:Body></soap:Envelope>
';

        $this->assertEquals($expectedResult, $parent->asXML());
    }
}
