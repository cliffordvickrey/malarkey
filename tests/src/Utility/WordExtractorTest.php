<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Utility;

use CliffordVickrey\Malarkey\Utility\WordExtractor;
use PHPUnit\Framework\TestCase;

class WordExtractorTest extends TestCase
{
    public function testExtractWords(): void
    {
        $extractor = new WordExtractor();
        $text = <<< EOT
I'd     buy    that    for   a     





    dollar.        
EOT;

        $this->assertEquals([
            "I'd",
            'buy',
            'that',
            'for',
            'a',
            '',
            'dollar.'
        ], $extractor->extractWords($text));
    }

    public function testExtractWordsFromEmptyString(): void
    {
        $extractor = new WordExtractor();
        $this->assertEquals([], $extractor->extractWords(''));
    }
}
