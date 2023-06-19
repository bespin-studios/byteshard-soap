<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\BSConnectors\BSSoap;

interface CookieInterface
{
    public function fetchCookie(): void;

    public function getCookieString(): string;

    public function getToken(): string;
}
