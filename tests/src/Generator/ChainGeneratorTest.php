<?php

declare(strict_types=1);

namespace Tests\CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Generator\ChainGenerator;
use CliffordVickrey\Malarkey\MarkovChain\Chain;
use CliffordVickrey\Malarkey\MarkovChain\ChainWithoutJsonSupport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use function json_decode;
use function json_encode;

class ChainGeneratorTest extends TestCase
{
    /** @var string */
    private $text;

    public function setUp(): void
    {
        $this->text = <<< EOT
The quick brown fox jumped over the lazy dogs.

The lazy dogs jumped over the quick brown fox.

The lazy fox jumped over the quick brown dogs.

The quick brown dogs jumped over the lazy fox.

EOT;
    }

    public function testGenerateChain(): void
    {
        $generator = new ChainGenerator();
        /** @var Chain $chain */
        $chain = $generator->generateChain($this->text);
        $json = <<< JSON
[
    {
        "words": [
            "The",
            "quick"
        ],
        "frequencies": {
            "brown": 2
        }
    },
    {
        "words": [
            "quick",
            "brown"
        ],
        "frequencies": {
            "fox": 1,
            "fox.": 1,
            "dogs.": 1,
            "dogs": 1
        }
    },
    {
        "words": [
            "brown",
            "fox"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "fox",
            "jumped"
        ],
        "frequencies": {
            "over": 2
        }
    },
    {
        "words": [
            "jumped",
            "over"
        ],
        "frequencies": {
            "the": 4
        }
    },
    {
        "words": [
            "over",
            "the"
        ],
        "frequencies": {
            "lazy": 2,
            "quick": 2
        }
    },
    {
        "words": [
            "the",
            "lazy"
        ],
        "frequencies": {
            "dogs.": 1,
            "fox.": 1
        }
    },
    {
        "words": [
            "lazy",
            "dogs."
        ],
        "frequencies": {
            "": 1
        }
    },
    {
        "words": [
            "dogs.",
            ""
        ],
        "frequencies": {
            "The": 2
        }
    },
    {
        "words": [
            "",
            "The"
        ],
        "frequencies": {
            "lazy": 2,
            "quick": 2
        }
    },
    {
        "words": [
            "The",
            "lazy"
        ],
        "frequencies": {
            "dogs": 1,
            "fox": 1
        }
    },
    {
        "words": [
            "lazy",
            "dogs"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "dogs",
            "jumped"
        ],
        "frequencies": {
            "over": 2
        }
    },
    {
        "words": [
            "the",
            "quick"
        ],
        "frequencies": {
            "brown": 2
        }
    },
    {
        "words": [
            "brown",
            "fox."
        ],
        "frequencies": {
            "": 1
        }
    },
    {
        "words": [
            "fox.",
            ""
        ],
        "frequencies": {
            "The": 2
        }
    },
    {
        "words": [
            "lazy",
            "fox"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "brown",
            "dogs."
        ],
        "frequencies": {
            "": 1
        }
    },
    {
        "words": [
            "brown",
            "dogs"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "lazy",
            "fox."
        ],
        "frequencies": {
            "": 1
        }
    }
]
JSON;

        $this->assertEquals(json_decode($json, true), json_decode(json_encode($chain) ?: '', true));
        $this->assertEquals([
            ['The', 'quick'],
            ['The', 'lazy']
        ], $chain->getStartingWordSequences());
        $this->assertTrue($chain->isEndOfSentence('dogs.'));
        $this->assertTrue($chain->isEndOfSentence('fox.'));
    }

    public function testGenerateChainIgnoreLineBreaks(): void
    {
        $generator = new ChainGenerator();
        /** @var Chain $chain */
        $chain = $generator->generateChain($this->text, 2, true);
        $json = <<< JSON
[
    {
        "words": [
            "The",
            "quick"
        ],
        "frequencies": {
            "brown": 2
        }
    },
    {
        "words": [
            "quick",
            "brown"
        ],
        "frequencies": {
            "fox": 1,
            "fox.": 1,
            "dogs.": 1,
            "dogs": 1
        }
    },
    {
        "words": [
            "brown",
            "fox"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "fox",
            "jumped"
        ],
        "frequencies": {
            "over": 2
        }
    },
    {
        "words": [
            "jumped",
            "over"
        ],
        "frequencies": {
            "the": 4
        }
    },
    {
        "words": [
            "over",
            "the"
        ],
        "frequencies": {
            "lazy": 2,
            "quick": 2
        }
    },
    {
        "words": [
            "the",
            "lazy"
        ],
        "frequencies": {
            "dogs.": 1,
            "fox.": 1
        }
    },
    {
        "words": [
            "lazy",
            "dogs."
        ],
        "frequencies": {
            "The": 1
        }
    },
    {
        "words": [
            "dogs.",
            "The"
        ],
        "frequencies": {
            "lazy": 1,
            "quick": 1
        }
    },
    {
        "words": [
            "The",
            "lazy"
        ],
        "frequencies": {
            "dogs": 1,
            "fox": 1
        }
    },
    {
        "words": [
            "lazy",
            "dogs"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "dogs",
            "jumped"
        ],
        "frequencies": {
            "over": 2
        }
    },
    {
        "words": [
            "the",
            "quick"
        ],
        "frequencies": {
            "brown": 2
        }
    },
    {
        "words": [
            "brown",
            "fox."
        ],
        "frequencies": {
            "The": 1
        }
    },
    {
        "words": [
            "fox.",
            "The"
        ],
        "frequencies": {
            "lazy": 1,
            "quick": 1
        }
    },
    {
        "words": [
            "lazy",
            "fox"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "brown",
            "dogs."
        ],
        "frequencies": {
            "The": 1
        }
    },
    {
        "words": [
            "brown",
            "dogs"
        ],
        "frequencies": {
            "jumped": 1
        }
    },
    {
        "words": [
            "lazy",
            "fox."
        ],
        "frequencies": {
            "The": 1
        }
    }
]
JSON;
        $this->assertEquals(json_decode($json, true), json_decode(json_encode($chain) ?: '', true));
        $this->assertEquals([
            ['The', 'quick'],
            ['The', 'lazy']
        ], $chain->getStartingWordSequences());
        $this->assertTrue($chain->isEndOfSentence('dogs.'));
        $this->assertTrue($chain->isEndOfSentence('fox.'));
    }

    public function testGenerateChainLookBackLessThanOne(): void
    {
        $generator = new ChainGenerator();
        $this->expectExceptionMessage('LookBack cannot be less than 1');
        $generator->generateChain($this->text, 0);
    }

    public function testGenerateChainLookBackGreaterThanWordCount(): void
    {
        $generator = new ChainGenerator();
        $this->expectExceptionMessage('LookBack cannot be greater than the number of words in the text');
        $generator->generateChain($this->text, 41);
    }

    /**
     * @throws ReflectionException
     */
    public function testGenerateChainNoJsonSupport(): void
    {
        $generator = new ChainGenerator();
        $reflectionClass = new ReflectionClass($generator);
        $property = $reflectionClass->getProperty('jsonSupport');
        $property->setAccessible(true);
        $property->setValue($generator, false);
        $chain = $generator->generateChain($this->text);
        $this->assertInstanceOf(ChainWithoutJsonSupport::class, $chain);
    }

    public function testGenerateChainWithNoEndOfSentence(): void
    {
        $generator = new ChainGenerator();
        $chain = $generator->generateChain("I'll buy that for a dollar", 2, true);
        $this->assertTrue($chain->isEndOfSentence('dollar'));
    }

    public function testGenerateChainWithNoWords(): void
    {
        $generate = new ChainGenerator();
        $chain = $generate->generateChain('', 1);
        $this->assertTrue($chain->isEndOfSentence(''));
    }
}
