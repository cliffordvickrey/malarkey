<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Generator\ChainGenerator;
use CliffordVickrey\Malarkey\MarkovChain\Chain;
use CliffordVickrey\Malarkey\MarkovChain\ChainWithoutJsonSupport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class ChainGeneratorTest extends TestCase
{
    /** @var ChainGenerator */
    private $chainGenerator;
    /** @var string */
    private $text;

    public function setUp(): void
    {
        $this->chainGenerator = new ChainGenerator();
        $this->text = "I'd buy that for a dollar! I'd buy this for two dollars! I'd buy that for a dollar!";
    }

    public function testGenerateChain(): void
    {
        /** @var Chain $chain */
        $chain = $this->chainGenerator->generateChain($this->text);
        $this->assertInstanceOf(Chain::class, $chain);
        $this->assertEquals(
            array(
                0 =>
                    array(
                        'words' =>
                            array(
                                0 => 'I\'d',
                                1 => 'buy',
                            ),
                        'frequencies' =>
                            array(
                                'that' => 2,
                                'this' => 1,
                            ),
                        'startingSequence' => true,
                    ),
                1 =>
                    array(
                        'words' =>
                            array(
                                0 => 'buy',
                                1 => 'that',
                            ),
                        'frequencies' =>
                            array(
                                'for' => 2,
                            ),
                        'startingSequence' => false,
                    ),
                2 =>
                    array(
                        'words' =>
                            array(
                                0 => 'that',
                                1 => 'for',
                            ),
                        'frequencies' =>
                            array(
                                'a' => 2,
                            ),
                        'startingSequence' => false,
                    ),
                3 =>
                    array(
                        'words' =>
                            array(
                                0 => 'for',
                                1 => 'a',
                            ),
                        'frequencies' =>
                            array(
                                'dollar!' => 2,
                            ),
                        'startingSequence' => false,
                    ),
                4 =>
                    array(
                        'words' =>
                            array(
                                0 => 'a',
                                1 => 'dollar!',
                            ),
                        'frequencies' =>
                            array(
                                'I\'d' => 1,
                                '' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                5 =>
                    array(
                        'words' =>
                            array(
                                0 => 'dollar!',
                                1 => 'I\'d',
                            ),
                        'frequencies' =>
                            array(
                                'buy' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                6 =>
                    array(
                        'words' =>
                            array(
                                0 => 'buy',
                                1 => 'this',
                            ),
                        'frequencies' =>
                            array(
                                'for' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                7 =>
                    array(
                        'words' =>
                            array(
                                0 => 'this',
                                1 => 'for',
                            ),
                        'frequencies' =>
                            array(
                                'two' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                8 =>
                    array(
                        'words' =>
                            array(
                                0 => 'for',
                                1 => 'two',
                            ),
                        'frequencies' =>
                            array(
                                'dollars!' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                9 =>
                    array(
                        'words' =>
                            array(
                                0 => 'two',
                                1 => 'dollars!',
                            ),
                        'frequencies' =>
                            array(
                                'I\'d' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                10 =>
                    array(
                        'words' =>
                            array(
                                0 => 'dollars!',
                                1 => 'I\'d',
                            ),
                        'frequencies' =>
                            array(
                                'buy' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                11 =>
                    array(
                        'words' =>
                            array(
                                0 => 'dollar!',
                                1 => '',
                            ),
                        'frequencies' =>
                            array(
                                'I\'d' => 1,
                            ),
                        'startingSequence' => false,
                    ),
                12 =>
                    array(
                        'words' =>
                            array(
                                0 => '',
                                1 => 'I\'d',
                            ),
                        'frequencies' =>
                            array(
                                'buy' => 1,
                            ),
                        'startingSequence' => false,
                    ),
            ), $chain->jsonSerialize()
        );
    }

    public function testGetLastGeneratedChunkCount(): void
    {
        $this->chainGenerator->generateChain($this->text);
        $this->assertEquals(1, $this->chainGenerator->getLastGeneratedChunkCount());
    }

    public function testGetLastGeneratedWordCount(): void
    {
        $this->chainGenerator->generateChain($this->text);
        $this->assertEquals(18, $this->chainGenerator->getLastGeneratedWordCount());
    }

    public function testGenerateChainLookBehindLessThanOne(): void
    {
        $this->expectExceptionMessage('Look behind cannot be less than 1');
        $this->chainGenerator->generateChain($this->text, 0);
    }

    public function testGenerateChainLookBehindGreaterThanWordCount(): void
    {
        $chain = $this->chainGenerator->generateChain($this->text, 100);
        $this->assertEquals(19, $chain->getLookBehind());
    }

    public function testGenerateChainNoWords(): void
    {
        /** @var Chain $chain */
        $chain = $this->chainGenerator->generateChain('');
        $this->assertCount(2, $chain->jsonSerialize());
    }

    /**
     * @throws ReflectionException
     */
    public function testGenerateChainNoJsonSupport(): void
    {
        $reflectionClass = new ReflectionClass(ChainGenerator::class);
        $property = $reflectionClass->getProperty('jsonSupport');
        $property->setAccessible(true);
        $property->setValue($this->chainGenerator, false);
        $chain = $this->chainGenerator->generateChain($this->text);
        $this->assertInstanceOf(ChainWithoutJsonSupport::class, $chain);
    }
}