<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\MarkovChain;

use CliffordVickrey\Malarkey\MarkovChain\Chain;
use CliffordVickrey\Malarkey\MarkovChain\ChainAbstract;
use CliffordVickrey\Malarkey\MarkovChain\Link;
use CliffordVickrey\Malarkey\MarkovChain\Word;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use function serialize;
use function unserialize;

class ChainTest extends TestCase
{
    /** @var Chain */
    private $chain;

    public function setUp(): void
    {
        $this->chain = new Chain([
            new Link(['ein' => 1], new Word('Ich', true, false), new Word('bin', false, false)),
            new Link(['Berliner!' => 1], new Word('bin', false, false), new Word('ein', false, false)),
            new Link(['Ich' => 1], new Word('ein', false, false), new Word('Berliner!', false, true)),
            new Link(['bin' => 1], new Word('Berliner!', false, true), new Word('Ich', true, false))
        ]);
    }

    public function testConstructEmpty(): void
    {
        $this->expectExceptionMessage('Expected at least one link in the Markov chain');
        new Chain([]);
    }

    public function testConstructWithLinkHavingNoWords(): void
    {
        $this->expectExceptionMessage('Link must have at least one word');
        new Chain([new Link(['blah' => 1])]);
    }

    public function testConstructWithLinkHavingNoFrequencies(): void
    {
        $this->expectExceptionMessage('Link frequencies cannot be empty');
        new Chain([new Link([], new Word('blah'))]);
    }

    public function testConstructWithMismatchedLinks(): void
    {
        $this->expectExceptionMessage('Expected link to have 1 word(s); got 2');
        new Chain([
            new Link(['blah' => 1], new Word('blah')),
            new Link(['blah' => 1], new Word('blah'), new Word('blah'))
        ]);
    }

    public function testConstructWithIdenticalLinks(): void
    {
        $this->expectExceptionMessage('Link with word values "blah" is not unique to the chain');
        new Chain([new Link(['blah' => 1], new Word('blah')), new Link(['blah' => 1], new Word('blah'))]);
    }

    public function testJsonSerialize(): void
    {
        $payload = $this->chain->jsonSerialize();
        $this->assertEquals([
            [
                'words' => ['Ich', 'bin'],
                'frequencies' => ['ein' => 1]
            ],
            [
                'words' => ['bin', 'ein'],
                'frequencies' => ['Berliner!' => 1]
            ],
            [
                'words' => ['ein', 'Berliner!'],
                'frequencies' => ['Ich' => 1]
            ],
            [
                'words' => ['Berliner!', 'Ich'],
                'frequencies' => ['bin' => 1]
            ]
        ], $payload);
    }

    public function testSerialize(): void
    {
        $serialized = serialize($this->chain);
        /** @var Chain $unSerialized */
        $unSerialized = unserialize($serialized);
        $this->assertEquals($this->chain->jsonSerialize(), $unSerialized->jsonSerialize());
    }

    /**
     * @throws ReflectionException
     */
    public function testSerializeBadLookBehind(): void
    {
        $this->nullProperty('lookBehind');
        $this->expectExceptionMessage('Variable lookBehind has an unexpected type. Expected int; got NULL');
        unserialize(serialize($this->chain));
    }

    /**
     * @param string $propertyName
     * @throws ReflectionException
     */
    private function nullProperty(string $propertyName): void
    {
        $reflectionClass = new ReflectionClass(ChainAbstract::class);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($this->chain, null);
    }

    /**
     * @throws ReflectionException
     */
    public function testSerializeBadChain(): void
    {
        $this->nullProperty('chain');
        $this->expectExceptionMessage('Variable chain has an unexpected type. Expected array; got NULL');
        unserialize(serialize($this->chain));
    }

    /**
     * @throws ReflectionException
     */
    public function testSerializeBadFrequencies(): void
    {
        $this->nullProperty('frequencies');
        $this->expectExceptionMessage('Variable frequencies has an unexpected type. Expected array; got NULL');
        unserialize(serialize($this->chain));
    }

    /**
     * @throws ReflectionException
     */
    public function testSerializeBadStartingWordSequences(): void
    {
        $this->nullProperty('startingWordSequences');
        $this->expectExceptionMessage(
            'Variable startingWordSequences has an unexpected type. Expected array; got NULL'
        );
        unserialize(serialize($this->chain));
    }

    /**
     * @throws ReflectionException
     */
    public function testSerializeBadEndsOfSentencesMap(): void
    {
        $this->nullProperty('endsOfSentencesMap');
        $this->expectExceptionMessage(
            'Variable endsOfSentencesMap has an unexpected type. Expected array; got NULL'
        );
        unserialize(serialize($this->chain));
    }

    public function testGetLookBehind(): void
    {
        $this->assertEquals(2, $this->chain->getLookBehind());
    }

    public function testGetStartingWordSequences(): void
    {
        $this->assertEquals([['Ich', 'bin']], $this->chain->getStartingWordSequences());
    }

    public function testIsEndOfSentence(): void
    {
        $this->assertFalse($this->chain->isEndOfSentence('Ich'));
        $this->assertFalse($this->chain->isEndOfSentence('bin'));
        $this->assertFalse($this->chain->isEndOfSentence('ein'));
        $this->assertTrue($this->chain->isEndOfSentence('Berliner!'));
    }

    public function testGetStateFrequencies(): void
    {
        $this->assertEquals(['ein' => 1], $this->chain->getStateFrequencies('Ich', 'bin'));
        $this->assertEquals(['Berliner!' => 1], $this->chain->getStateFrequencies('bin', 'ein'));
        $this->assertEquals(['Ich' => 1], $this->chain->getStateFrequencies('ein', 'Berliner!'));
        $this->assertEquals(['bin' => 1], $this->chain->getStateFrequencies('Berliner!', 'Ich'));
    }

    public function testGetStateFrequenciesBadWords(): void
    {
        $this->expectExceptionMessage('Word combination "ich, bin" not found in chain');
        $this->chain->getStateFrequencies('ich', 'bin');
    }

    public function testGetStateFrequenciesTooFewWords(): void
    {
        $this->expectExceptionMessage('Word combination "Ich" not found in chain');
        $this->chain->getStateFrequencies('Ich');
    }

    public function testGetStateFrequenciesTooManyWords(): void
    {
        $this->expectExceptionMessage('Word combination "Ich, bin, ein" not found in chain');
        $this->chain->getStateFrequencies('Ich', 'bin', 'ein');
    }
}
