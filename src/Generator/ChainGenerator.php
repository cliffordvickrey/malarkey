<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Exception\InvalidArgumentException;
use CliffordVickrey\Malarkey\MarkovChain\Chain;
use CliffordVickrey\Malarkey\MarkovChain\ChainBuilder;
use CliffordVickrey\Malarkey\Utility\WordExtractor;
use CliffordVickrey\Malarkey\Utility\WordExtractorInterface;
use function array_merge;
use function array_shift;
use function array_slice;
use function array_values;
use function count;
use function current;
use function end;
use function reset;

/**
 * Markov chain generator. Takes text and builds a stochastic model describing the flow of the words therein.
 */
class ChainGenerator implements ChainGeneratorInterface
{
    /** @var int|null */
    private $lastGeneratedChunkCount;
    /** @var int|null */
    private $lastGeneratedWordCount;
    /** @var WordExtractorInterface */
    private $wordExtractor;

    /**
     * ChainGenerator constructor.
     * @param WordExtractorInterface|null $wordExtractor
     */
    public function __construct(?WordExtractorInterface $wordExtractor = null)
    {
        $this->wordExtractor = $wordExtractor ?? new WordExtractor();
    }

    /**
     * This is a very procedural implementation. What follows is not pretty, or particularly memory efficient, but
     * rather fast!
     * (@inheritDoc)
     * @return Chain
     */
    public function generateChain(string $text, int $lookBehind = 2)
    {
        if ($lookBehind < 1) {
            throw new InvalidArgumentException('Look behind cannot be less than 1');
        }

        $words = $this->extractWords($text);

        // ensure that look behind isn't greater than the word count
        $wordAndLineBreakCount = count($words);
        if ($wordAndLineBreakCount < $lookBehind) {
            $lookBehind = $wordAndLineBreakCount;
        }

        // ensure that the end of the chain is linked to the beginning
        $words = array_merge($words, array_slice($words, 0, $lookBehind));

        // frequencies: given state of chain X looking back Y words, what are the likely next words in the chain?
        /** @var array<string, mixed> $frequenciesTree */
        $frequenciesTree = [];
        /** @var array<int, array> $frequenciesTable */
        $frequenciesTable = [];

        // store information about every discrete sequence of words
        $maxSequenceId = -1;
        /** @var array<string, mixed> $sequenceIds */
        $sequenceIds = [];

        // here, we memoize information about each word
        $endOfChunk = false;
        /** @var array<string, bool> $startsOfChunksMemo */
        $startsOfChunksMemo = [];

        // starting word sequences: what words can be used at the start of the chain?
        /** @var array<int, array<int, string>> $startingSequences */
        $startingSequences = [];

        // statistics about the text we're working from
        $chunkCount = 0;
        $wordCount = 0;

        // valid: whether or not to add to the frequencies table
        $valid = false;

        /** @var string[] $wordsInSequence */
        $wordsInSequence = [];

        foreach ($words as $i => $word) {
            // convention: empty strings constitute the end of a chunk
            $isNewLine = '' === $word;

            // resolve whether the word is at the end of a chunk
            if (!$i) {
                $startOfChunk = true;
            } elseif ($isNewLine || !$endOfChunk) {
                $startOfChunk = false;
            } else {
                $startOfChunk = true;
            }

            $startsOfChunksMemo[] = $startOfChunk;
            $endOfChunk = $isNewLine;

            if (!$valid) {
                $valid = $lookBehind === count($wordsInSequence);
            }

            if ($valid) {
                // use array references to build our frequencies struct
                $frequenciesTreeRef = &$frequenciesTree;
                $sequenceIdRef = &$sequenceIds;

                foreach ($wordsInSequence as $wordInSequence) {
                    if (!isset($sequenceIdRef[$wordInSequence])) {
                        $frequenciesTreeRef[$wordInSequence] = [];
                        $sequenceIdRef[$wordInSequence] = [];
                    }

                    $frequenciesTreeRef = &$frequenciesTreeRef[$wordInSequence];
                    $sequenceIdRef = &$sequenceIdRef[$wordInSequence];
                }

                if (empty($frequenciesTreeRef)) {
                    $frequenciesTreeRef = [$word => 1];
                    $sequenceIdRef = ++$maxSequenceId;
                    $frequenciesTable[] = [
                        'words' => $wordsInSequence,
                        'frequencies' => 0,
                        'startingSequence' => false
                    ];
                } elseif (empty($frequenciesTreeRef[$word])) {
                    $frequenciesTreeRef[$word] = 1;
                } else {
                    $frequenciesTreeRef[$word]++;
                }

                $sequenceIdRef = (int)$sequenceIdRef;
                $frequenciesTable[$sequenceIdRef]['frequencies'] = $frequenciesTreeRef;

                if ($startsOfChunksMemo[0]) {
                    $startingSequences[$sequenceIdRef] = $wordsInSequence;
                    $frequenciesTable[$sequenceIdRef]['startingSequence'] = true;
                }

                // update statistics
                if ($isNewLine) {
                    $chunkCount++;
                } else {
                    $wordCount++;
                }

                // shift off the first word in the sequence
                array_shift($wordsInSequence);
                array_shift($startsOfChunksMemo);
            }

            $wordsInSequence[] = $word;
        }

        // unlink references
        unset($frequenciesTreeRef, $sequenceIdRef);

        // remove sequence ID array keys from starting sequences
        $startingSequences = array_values($startingSequences);

        // save the metrics
        $this->lastGeneratedChunkCount = $chunkCount;
        $this->lastGeneratedWordCount = $wordCount;

        return $this->build($frequenciesTable, $frequenciesTree, $lookBehind, $startingSequences);
    }

    /**
     * @param string $text
     * @return string[]
     */
    private function extractWords(string $text): array
    {
        // create a list of words from the source text
        $words = $this->wordExtractor->extractWords($text);

        // ensure there's at least one word!
        if (empty($words)) {
            $words = [' '];
        }

        // ensure that the words list end with a line break
        end($words);
        if ('' !== current($words)) {
            $words[] = '';
        }
        reset($words);

        return $words;
    }

    /**
     * @param array<int, array> $frequenciesTable
     * @param array<string, mixed> $frequenciesTree
     * @param int $lookBehind
     * @param array<int, array<int, string>> $startingSequences
     * @return Chain
     */
    private function build(
        array $frequenciesTable,
        array $frequenciesTree,
        int $lookBehind,
        array $startingSequences
    ): Chain
    {
        // build the chain
        $chainBuilder = new ChainBuilder();
        $chainBuilder->setFrequenciesTable($frequenciesTable);
        $chainBuilder->setFrequenciesTree($frequenciesTree);
        $chainBuilder->setLookBehind($lookBehind);
        $chainBuilder->setPossibleStartingSequences($startingSequences);
        return Chain::build($chainBuilder);
    }

    /**
     * @return int|null
     */
    public function getLastGeneratedChunkCount(): ?int
    {
        return $this->lastGeneratedChunkCount;
    }

    /**
     * @return int|null
     */
    public function getLastGeneratedWordCount(): ?int
    {
        return $this->lastGeneratedWordCount;
    }
}
