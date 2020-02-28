<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Generator\TextGeneratorOptions;
use PHPUnit\Framework\TestCase;
use stdClass;

class TextGeneratorOptionsTest extends TestCase
{
    public function testGetChunkSeparator(): void
    {
        $options = new TextGeneratorOptions();
        $options->setChunkSeparator('something');
        $this->assertEquals('something', $options->getChunkSeparator());
    }

    public function testGetMaxChunks(): void
    {
        $options = new TextGeneratorOptions();
        $options->setMaxChunks(10);
        $this->assertEquals(10, $options->getMaxChunks());
    }

    public function testGetMaxSentences(): void
    {
        $options = new TextGeneratorOptions();
        $options->setMaxSentences(100);
        $this->assertEquals(100, $options->getMaxSentences());
    }

    public function testGetMaxWords(): void
    {
        $options = new TextGeneratorOptions();
        $options->setMaxWords(1000);
        $this->assertEquals(1000, $options->getMaxWords());
    }

    public function testGetWordSeparator(): void
    {
        $options = new TextGeneratorOptions();
        $options->setWordSeparator('something');
        $this->assertEquals('something', $options->getWordSeparator());
    }

    public function testFromArray(): void
    {
        $options = TextGeneratorOptions::fromArray([
            'chunkSeparator' => 'something',
            'maxChunks' => 10,
            'maxSentences' => 100,
            'maxWords' => 1000,
            'wordSeparator' => 'something else'
        ]);

        $this->assertEquals('something', $options->getChunkSeparator());
        $this->assertEquals(10, $options->getMaxChunks());
        $this->assertEquals(100, $options->getMaxSentences());
        $this->assertEquals(1000, $options->getMaxWords());
        $this->assertEquals('something else', $options->getWordSeparator());
    }

    public function testFromArrayBadChunkSeparator(): void
    {
        $this->expectExceptionMessage('Invalid value for option "chunkSeparator"');
        TextGeneratorOptions::fromArray(['chunkSeparator' => []]);
    }

    public function testFromArrayBadMaxChunks(): void
    {
        $this->expectExceptionMessage('Invalid value for option "maxChunks"');
        TextGeneratorOptions::fromArray(['maxChunks' => []]);
    }

    public function testFromArrayBadMaxSentences(): void
    {
        $this->expectExceptionMessage('Invalid value for option "maxSentences"');
        TextGeneratorOptions::fromArray(['maxSentences' => []]);
    }

    public function testFromArrayBadMaxWords(): void
    {
        $this->expectExceptionMessage('Invalid value for option "maxWords"');
        TextGeneratorOptions::fromArray(['maxWords' => []]);
    }

    public function testFromArrayBadWordSeparator(): void
    {
        $this->expectExceptionMessage('Invalid value for option "wordSeparator"');
        TextGeneratorOptions::fromArray(['wordSeparator' => []]);
    }
}