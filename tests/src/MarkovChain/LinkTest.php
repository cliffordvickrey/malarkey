<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\MarkovChain;

use CliffordVickrey\Malarkey\MarkovChain\Link;
use CliffordVickrey\Malarkey\MarkovChain\Word;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testGetStateFrequencies(): void
    {
        $link = new Link(['b' => 1], new Word('a'));
        $this->assertEquals(['b' => 1], $link->getStateFrequencies());
    }

    public function testGetWords(): void
    {
        $link = new Link(['b' => 1], new Word('a'));
        $this->assertEquals([new Word('a')], $link->getWords());
    }
}
