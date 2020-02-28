<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Generator\ChainGenerator;
use CliffordVickrey\Malarkey\Generator\TextGenerator;
use CliffordVickrey\Malarkey\Generator\TextGeneratorOptions;
use CliffordVickrey\Malarkey\MarkovChain\Chain;
use CliffordVickrey\Malarkey\MarkovChain\ChainBuilder;
use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;
use PHPUnit\Framework\TestCase;
use function call_user_func_array;
use function substr_count;

class TextGeneratorTest extends TestCase
{
    /** @var ChainInterface */
    private static $chain;
    /** @var TextGenerator */
    private $textGenerator;

    public static function setUpBeforeClass(): void
    {
        $text = "I'll buy that for a dollar! I'll buy this for two dollars! I'll buy that for a dollar!";
        $chainGenerator = new ChainGenerator();
        self::$chain = $chainGenerator->generateChain($text);
    }

    public function setUp(): void
    {
        $this->textGenerator = new TextGenerator();
    }

    public function testGenerateTextOneChunk(): void
    {
        $output = $this->textGenerator->generateText(self::$chain, ['maxChunks' => 1]);
        $this->assertGreaterThanOrEqual(1, substr_count($output, '!'));
    }

    public function testGenerateNullOptions(): void
    {
        $output = $this->textGenerator->generateText(self::$chain);
        $this->assertGreaterThanOrEqual(1, substr_count($output, '!'));
    }

    public function testGenerateWithTextOptions(): void
    {
        $output = $this->textGenerator->generateText(self::$chain, new TextGeneratorOptions());
        $this->assertGreaterThanOrEqual(1, substr_count($output, '!'));
    }

    public function testGenerateTextOneSentence(): void
    {
        $output = $this->textGenerator->generateText(self::$chain, ['maxSentences' => 1]);
        $this->assertContains($output, ["I'll buy that for a dollar!", "I'll buy this for two dollars!"]);
    }

    public function testGenerateTextOneWord(): void
    {
        $output = $this->textGenerator->generateText(self::$chain, ['maxWords' => 1]);
        $this->assertEquals("I'll", $output);
    }

    public function testGenerateTextInvalidOptions(): void
    {
        $this->expectExceptionMessage(
            'Variable options has an unexpected type. Expected array, NULL, or instance of'
            . ' CliffordVickrey\Malarkey\Generator\TextGeneratorOptionsInterface; got boolean'
        );
        call_user_func_array([$this->textGenerator, 'generateText'], [self::$chain, false]);
    }

    public function testGenerateTextZeroMaxChunks(): void
    {
        $this->assertEquals('', $this->textGenerator->generateText(self::$chain, ['maxChunks' => 0]));
    }

    public function testGenerateTextZeroMaxSentences(): void
    {
        $this->assertEquals('', $this->textGenerator->generateText(self::$chain, ['maxSentences' => 0]));
    }

    public function testGenerateTextZeroMaxWords(): void
    {
        $this->assertEquals('', $this->textGenerator->generateText(self::$chain, ['maxWords' => 0]));
    }

    public function testGenerateTextNoPossibleStartingSequences(): void
    {
        $chain = new Chain();
        $chain->add(['blah'], ['blah' => 1], false);
        $this->expectExceptionMessage('Cannot generate text; Markov chain has no starting point');
        $this->textGenerator->generateText($chain);
    }

    public function testGenerateTextEmptyPossibleStartingSequence(): void
    {
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setPossibleStartingSequences([[]]);
        $chainBuilder->setLookBehind(1);
        $chainBuilder->setFrequenciesTable([
            ['words' => ['a']],
            ['frequencies' => ['a' => 1]]
        ]);
        $chainBuilder->setFrequenciesTree(['a' => ['a' => 1]]);

        $this->expectExceptionMessage('Cannot generate text; starting words are empty');
        $this->textGenerator->generateText(Chain::build($chainBuilder));
    }

    public function testGenerateTextBadFrequencies(): void
    {
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setPossibleStartingSequences([['a']]);
        $chainBuilder->setLookBehind(1);
        $chainBuilder->setFrequenciesTable([
            ['words' => ['a']],
            ['frequencies' => ['a' => 1]]
        ]);
        $chainBuilder->setFrequenciesTree(['a' => ['b' => 0]]);

        $this->expectExceptionMessage('Cannot generate text; cannot find the next word in the chain');
        $this->textGenerator->generateText(Chain::build($chainBuilder));
    }

    public function testGenerateTextMultipleLineBreaks(): void
    {
        $chain = new Chain();
        $chain->add([''], ['' => 1]);
        $this->assertEquals('', $this->textGenerator->generateText($chain, ['maxWords' => 100]));
    }

    public function testGenerateTextInvalidMaxChunks(): void
    {
        $this->expectExceptionMessage('Maximum chunks must be NULL or greater than -1');
        $this->textGenerator->generateText(self::$chain, ['maxChunks' => -1]);
    }

    public function testGenerateTextInvalidMaxSentences(): void
    {
        $this->expectExceptionMessage('Maximum sentences must be NULL or greater than -1');
        $this->textGenerator->generateText(self::$chain, ['maxSentences' => -1]);
    }

    public function testGenerateTextInvalidMaxWords(): void
    {
        $this->expectExceptionMessage('Maximum words must be NULL or greater than -1');
        $this->textGenerator->generateText(self::$chain, ['maxWords' => -1]);
    }
}