<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Chain;

use CliffordVickrey\Malarkey\MarkovChain\ChainBuilder;
use PHPUnit\Framework\TestCase;

class ChainBuilderTest extends TestCase
{
    public function testGetLookBehind(): void
    {
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setLookBehind(1);
        $this->assertEquals(1, $chainBuilder->getLookBehind());
    }

    public function testGetFrequenciesTable(): void
    {
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setFrequenciesTable([['words' => 'a']]);
        $this->assertEquals([['words' => 'a']], $chainBuilder->getFrequenciesTable());
    }

    public function testGetFrequenciesTree(): void
    {
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setFrequenciesTree(['a' => ['b' => 1]]);
        $this->assertEquals(['a' => ['b' => 1]], $chainBuilder->getFrequenciesTree());
    }

    public function testGetPossibleStartingSequences(): void
    {
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setPossibleStartingSequences([['a', 'b']]);
        $this->assertEquals([['a', 'b']], $chainBuilder->getPossibleStartingSequences());
    }

    public function testValidate(): void
    {
        $chainBuilder = new ChainBuilder();
        $this->expectExceptionMessage(
            'CliffordVickrey\Malarkey\MarkovChain\ChainBuilder is invalid; properties frequenciesTable, '
            . 'frequenciesTree, lookBehind, possibleStartingSequences are NULL'
        );
        $chainBuilder->validate();
    }
}
