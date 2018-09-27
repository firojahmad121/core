<?php

namespace Webkul\UVDesk\CoreBundle\CLI;

/**
 * A collection of ANSI Escape Sequences that can be used to make changes to the console output.
 * 
 * Learn more:
 * - https://en.wikipedia.org/wiki/ANSI_escape_code
 * - http://tldp.org/HOWTO/Bash-Prompt-HOWTO/x361.html
 * - https://stackoverflow.com/questions/37774983/clearing-the-screen-by-printing-a-character
*/
final class ANSIEscapeSequence
{
    const MOVE_CURSOR_UP = "\033[%dA";
    const MOVE_CURSOR_DOWN = "\033[%dB";
    const MOVE_CURSOR_FORWARD = "\033[%dC";
    const MOVE_CURSOR_BACKWARD = "\033[%dD";
    const MOVE_CURSOR_HOME = "\033[H";
    const POSITION_CURSOR = "\033[%d;%dH";
    const CURRENT_CURSOR_POSITION = "\033[6n";
    const CLEAR_SCREEN = "\033[2J";
    const ERASE_EOL = "\033[K";

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
