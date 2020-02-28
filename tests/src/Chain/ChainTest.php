<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Chain;

use CliffordVickrey\Malarkey\MarkovChain\Chain;
use CliffordVickrey\Malarkey\MarkovChain\ChainAbstract;
use CliffordVickrey\Malarkey\MarkovChain\ChainBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use function json_encode;
use function serialize;
use function unserialize;

class ChainTest extends TestCase
{
    /** @var Chain */
    private $chain;

    public function setUp(): void
    {
        $this->chain = new Chain();
        $this->chain->add(["I'd", 'buy'], ['that' => 1]);
        $this->chain->add(['buy', 'that'], ['for' => 1]);
        $this->chain->add(['that', 'for'], ['a' => 1]);
        $this->chain->add(['for', 'a'], ['dollar!' => 1]);
        $this->chain->add(['a', 'dollar!'], ["I'd" => 1]);
        $this->chain->add(['dollar!', "I'd"], ['buy' => 1]);
    }

    public function testJsonSerialize(): void
    {
        $this->assertEquals([
            [
                'words' => ["I'd", 'buy'],
                'frequencies' => ['that' => 1],
                'startingSequence' => true
            ],
            [
                'words' => ['buy', 'that'],
                'frequencies' => ['for' => 1],
                'startingSequence' => false
            ],
            [
                'words' => ['that', 'for'],
                'frequencies' => ['a' => 1],
                'startingSequence' => false
            ],
            [
                'words' => ['for', 'a'],
                'frequencies' => ['dollar!' => 1],
                'startingSequence' => false
            ],
            [
                'words' => ['a', 'dollar!'],
                'frequencies' => ["I'd" => 1],
                'startingSequence' => false
            ],
            [
                'words' => ['dollar!', "I'd"],
                'frequencies' => ['buy' => 1],
                'startingSequence' => false
            ]
        ], $this->chain->jsonSerialize());
    }

    public function testGetLookBehind(): void
    {
        $this->assertEquals(2, $this->chain->getLookBehind());
    }

    public function testGetPossibleStartingSequences(): void
    {
        $this->assertEquals([["I'd", 'buy']], $this->chain->getPossibleStartingSequences());
    }

    public function testGetFrequenciesBySequence(): void
    {
        $frequenciesTable = $this->chain->jsonSerialize();
        foreach ($frequenciesTable as $row) {
            $this->assertEquals($row['frequencies'], $this->chain->getFrequenciesBySequence(...$row['words']));
        }
    }

    public function testGetFrequenciesBySequenceOutOfBounds(): void
    {
        $this->expectExceptionMessage('Word combination "two, dollars!" not found in chain');
        $this->chain->getFrequenciesBySequence('two', 'dollars!');
    }

    public function testCount(): void
    {
        $this->assertCount(6, $this->chain);
    }

    public function testSerialize(): void
    {
        $serialized = serialize($this->chain);
        $unSerialized = unserialize($serialized);
        $this->assertEquals(json_encode($this->chain), json_encode($unSerialized));
    }

    /**
     * @throws ReflectionException
     */
    public function testUnSerializeNullFrequenciesTable(): void
    {
        $this->nullProperty('frequenciesTable');
        $serialized = serialize($this->chain);
        $this->expectExceptionMessage('Variable frequenciesTable has an unexpected type. Expected array; got NULL');
        unserialize($serialized);
    }

    /**
     * @throws ReflectionException
     */
    public function testUnSerializeNullFrequenciesTree(): void
    {
        $this->nullProperty('frequenciesTree');
        $serialized = serialize($this->chain);
        $this->expectExceptionMessage('Variable frequenciesTree has an unexpected type. Expected array; got NULL');
        unserialize($serialized);
    }

    /**
     * @throws ReflectionException
     */
    public function testUnSerializeNullLookBehind(): void
    {
        $this->nullProperty('lookBehind');
        $serialized = serialize($this->chain);
        $this->expectExceptionMessage('Variable lookBehind has an unexpected type. Expected int; got NULL');
        unserialize($serialized);
    }

    /**
     * @throws ReflectionException
     */
    public function testUnSerializeNullPossibleStartingSequences(): void
    {
        $this->nullProperty('possibleStartingSequences');
        $serialized = serialize($this->chain);
        $this->expectExceptionMessage(
            'Variable possibleStartingSequences has an unexpected type. Expected array; got NULL'
        );
        unserialize($serialized);
    }

    public function testAddNoWords(): void
    {
        $chain = new Chain();
        $this->expectExceptionMessage('Sequence must have at least one word');
        $chain->add([], ['a' => 1]);
    }

    public function testAddNoFrequencies(): void
    {
        $chain = new Chain();
        $this->expectExceptionMessage('Sequence frequencies cannot be empty');
        $chain->add(['a'], []);
    }

    public function testAddInvalidNumberOfWords(): void
    {
        $this->expectExceptionMessage('Expected sequence to have 2 word(s); got 3');
        $this->chain->add(['for', 'two',' dollars'], ['a' => 1]);
    }

    public function testAddSequenceNonUnique(): void
    {
        $this->expectExceptionMessage('Sequence with word values "a, dollar!" is not unique to the chain');
        $this->chain->add(['a', 'dollar!'], ['a' => 1]);
    }

    public function testBuild(): void
    {
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setFrequenciesTable([]);
        $chainBuilder->setFrequenciesTree([]);
        $chainBuilder->setLookBehind(1);
        $chainBuilder->setPossibleStartingSequences([]);
        $this->assertInstanceOf(Chain::class, Chain::build($chainBuilder));
    }

    /**
     * @param string $property
     * @throws ReflectionException
     */
    private function nullProperty(string $property): void
    {
        $reflectionClass = new ReflectionClass(ChainAbstract::class);
        $property = $reflectionClass->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($this->chain, null);
    }
}