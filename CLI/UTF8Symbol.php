<?php

namespace Webkul\UVDesk\CoreBundle\CLI;

/**
 * A collection of UTF-8 encoded symbols.
 * 
 * Learn more:
 * - https://www.utf8-chartable.de/unicode-utf8-table.pl?start=9984&number=128&names=2&utf8=string-literal
*/
final class UTF8Symbol
{
    const CHECK = "\xe2\x9c\x94";
    const CLOSE = "\xe2\x9c\x96";
    const NOTICE = "\xe2\x9d\x97";
    const NOTICE_INVERTED = "\xe2\x9d\x95";

    /**
     * Disable cloning of this class.
    */
    private function __clone()
    {
        // ...
    }

    /**
     * Disable instantion of this class.
    */
    private function __construct()
    {
        // ...
    }
}
