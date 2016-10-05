<?php

namespace Naneau\Obfuscator;

class ScopedStringScrambler implements StringScramblerInterface
{
    protected $dictionary = [];
    protected $currentIdentifier = 'a';

    /**
     * @param string $string
     * @return string
     */
    public function scramble($string)
    {
        if (isset($this->dictionary[$string])) {
            return $this->dictionary[$string];
        }

        $scrambledString = $this->currentIdentifier++;
        $this->dictionary[$string] = $scrambledString;

        return $scrambledString;
    }
}
