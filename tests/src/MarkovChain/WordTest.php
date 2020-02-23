<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\MarkovChain;

use CliffordVickrey\Malarkey\MarkovChain\Word;
use PHPUnit\Framework\TestCase;

class WordTest extends TestCase
{
    public function testConstruct(): void
    {
        $word = new Word('Hello', true, false);
        $this->assertEquals('Hello', (string)$word);
        $this->assertTrue($word->isStartOfSentence());
        $this->assertFalse($word->isEndOfSentence());

        $word = new Word('world!', false, true);
        $this->assertEquals('world!', (string)$word);
        $this->assertFalse($word->isStartOfSentence());
        $this->assertTrue($word->isEndOfSentence());
    }
}