<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Generator\ChainGenerator;
use CliffordVickrey\Malarkey\Generator\TextGenerator;
use CliffordVickrey\Malarkey\MarkovChain\ChainAbstract;
use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use function str_word_count;
use function substr_count;

class TextGeneratorTest extends TestCase
{
    /** @var ChainInterface */
    private static $chain;
    /** @var ChainInterface */
    private static $emptyChain;
    /** @var ChainInterface */
    private static $linearChain;
    /** @var ChainInterface */
    private static $longChain;
    /** @var TextGenerator */
    private $textGenerator;

    public static function setUpBeforeClass(): void
    {
        $text = <<< EOT
The quick brown fox jumped over the lazy dogs.

The lazy dogs jumped over the quick brown fox.

The lazy fox jumped over the quick brown dogs.

The quick brown dogs jumped over the lazy fox.
EOT;

        $linearText = <<< EOT
This is a sentence.

And here's another.
EOT;

        $chainGenerator = new ChainGenerator();
        self::$chain = $chainGenerator->generateChain($text);
        self::$linearChain = $chainGenerator->generateChain($linearText);
        self::$longChain = $chainGenerator->generateChain($text, 20);

        self::$emptyChain = new class implements ChainInterface {
            /**
             * (@inheritDoc)
             */
            public function getCoherence(): int
            {
                return 1;
            }

            /**
             * (@inheritDoc)
             */
            public function getStateFrequencies(string ...$words): array
            {
                return ['' => 1];
            }

            /**
             * (@inheritDoc)
             */
            public function getStartingWordSequences(): array
            {
                return [['']];
            }

            /**
             * (@inheritDoc)
             */
            public function isEndOfSentence(string $word): bool
            {
                return false;
            }
        };
    }

    public function setUp(): void
    {
        $this->textGenerator = new TextGenerator();
    }

    public function testGenerateText(): void
    {
        $text = $this->textGenerator->generateText(self::$chain, 1, 9);

        $this->assertContains($text, [
            'The lazy dogs.',
            'The lazy dogs jumped over the lazy dogs.',
            'The lazy dogs jumped over the lazy fox',
            'The lazy dogs jumped over the lazy fox.',
            'The lazy dogs jumped over the quick brown dogs',
            'The lazy dogs jumped over the quick brown dogs.',
            'The lazy dogs jumped over the quick brown fox',
            'The lazy dogs jumped over the quick brown fox.',
            'The lazy fox jumped over the lazy dogs.',
            'The lazy fox jumped over the lazy fox',
            'The lazy fox jumped over the lazy fox.',
            'The lazy fox jumped over the quick brown dogs',
            'The lazy fox jumped over the quick brown dogs.',
            'The lazy fox jumped over the quick brown fox',
            'The lazy fox jumped over the quick brown fox.',
            'The lazy fox jumped over the quick brown dogs.',
            'The lazy fox.',
            'The quick brown dogs.',
            'The quick brown dogs jumped over the lazy dogs.',
            'The quick brown dogs jumped over the lazy fox',
            'The quick brown dogs jumped over the lazy fox.',
            'The quick brown dogs jumped over the quick brown',
            'The quick brown fox.',
            'The quick brown fox jumped over the lazy dogs.',
            'The quick brown fox jumped over the lazy fox',
            'The quick brown fox jumped over the lazy fox.',
            'The quick brown fox jumped over the quick brown',
        ]);
    }

    public function testGenerateTextWithNullMaxWords(): void
    {
        $this->assertEquals(2, substr_count($this->textGenerator->generateText(self::$linearChain, 2, null), '.'));
    }

    public function testGenerateTextWithNullMaxSentences(): void
    {
        $this->assertEquals(100, str_word_count($this->textGenerator->generateText(self::$chain, null, 100)));
    }


    public function testGenerateTextWithNegativeMaxSentences(): void
    {
        $this->expectExceptionMessage('Maximum sentences must be NULL or greater than -1');
        $this->textGenerator->generateText(self::$chain, -1);
    }

    public function testGenerateTextWithNegativeMaxWords(): void
    {
        $this->expectExceptionMessage('Maximum words must be NULL or greater than -1');
        $this->textGenerator->generateText(self::$chain, 1, -1);
    }

    public function testGenerateTextWithMaxSentencesAndWordsBothNull(): void
    {
        $this->expectExceptionMessage('Maximum sentences and maximum words cannot both be NULL');
        $this->textGenerator->generateText(self::$chain, null, null);
    }

    public function testGenerateTextWithZeroMaxSentences(): void
    {
        $this->assertEquals('', $this->textGenerator->generateText(self::$chain, 0));
    }

    public function testGenerateTextWithZeroMaxWords(): void
    {
        $this->assertEquals('', $this->textGenerator->generateText(self::$chain, null, 0));
    }

    public function testGenerateTextWithOneMaxWord(): void
    {
        $this->assertEquals(1, str_word_count($this->textGenerator->generateText(self::$chain, null, 1)));
    }

    public function testGenerateTextWithOneMaxSentence(): void
    {
        $this->assertEquals(1, substr_count($this->textGenerator->generateText(self::$longChain, 1), '.'));
    }

    public function testGenerateTextWithEmptyChain(): void
    {
        $this->assertEquals('', $this->textGenerator->generateText(self::$emptyChain, null, 100));
    }

    /**
     * @throws ReflectionException
     */
    public function testGenerateTextWithNoStartingCombinations(): void
    {
        $chain = clone self::$chain;
        $reflectionClass = new ReflectionClass(ChainAbstract::class);
        $property = $reflectionClass->getProperty('startingWordSequences');
        $property->setAccessible(true);
        $property->setValue($chain, []);

        $this->expectExceptionMessage('Cannot generate text; Markov chain has no starting point');
        $this->textGenerator->generateText($chain, 1);
    }

    /**
     * @throws ReflectionException
     */
    public function testGenerateTextWithBadStartingCombinations(): void
    {
        $chain = clone self::$chain;
        $reflectionClass = new ReflectionClass(ChainAbstract::class);
        $property = $reflectionClass->getProperty('startingWordSequences');
        $property->setAccessible(true);
        $property->setValue($chain, [[]]);

        $this->expectExceptionMessage('Cannot generate text; starting words are empty');
        $this->textGenerator->generateText($chain, 1);
    }

    /**
     * @throws ReflectionException
     */
    public function testGenerateTextWithChainToNowhere(): void
    {
        $chain = clone self::$linearChain;
        $reflectionClass = new ReflectionClass(ChainAbstract::class);
        $property = $reflectionClass->getProperty('frequencies');
        $property->setAccessible(true);
        $property->setValue($chain, [
            'This' => [
                'is' => [
                    'uh oh!' => 0
                ]
            ],
            'And' => [
                "here's" => [
                    'ruh roh' => -1
                ]
            ]
        ]);

        $this->expectExceptionMessage('Cannot generate text; cannot find the next word in the chain');
        $this->textGenerator->generateText($chain, 1);
    }
}
