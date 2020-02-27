<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Exception\InvalidArgumentException;
use CliffordVickrey\Malarkey\Exception\RuntimeException;
use CliffordVickrey\Malarkey\Exception\TypeException;
use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;
use CliffordVickrey\Malarkey\Utility\ArrayUtilities;
use CliffordVickrey\Malarkey\Utility\EndOfSentenceResolver;
use CliffordVickrey\Malarkey\Utility\EndOfSentenceResolverInterface;
use function array_fill;
use function array_keys;
use function array_map;
use function array_merge;
use function array_shift;
use function array_slice;
use function array_sum;
use function count;
use function current;
use function implode;
use function is_array;
use function key;
use function mt_rand;
use function sprintf;

/**
 * Takes a Markov chain object to produce random text
 */
class TextGenerator implements TextGeneratorInterface
{
    /** @var ArrayUtilities */
    private $arrayUtilities;
    /** @var EndOfSentenceResolverInterface */
    private $endOfSentenceResolver;

    public function __construct(?EndOfSentenceResolverInterface $endOfSentenceResolver = null)
    {
        $this->arrayUtilities = new ArrayUtilities();
        $this->endOfSentenceResolver = $endOfSentenceResolver ?? new EndOfSentenceResolver();
    }

    /**
     * (@inheritDoc)
     */
    public function generateText(ChainInterface $chain, $options = null): string
    {
        // extract parameters

        $options = self::buildOptions($options);
        list ($maxChunks, $maxWords, $maxSentences) = self::getMaximumChunksSentencesAndWords($options);
        $chunkSeparator = $options->getChunkSeparator();
        $wordSeparator = $options->getWordSeparator();

        // set up initial state:

        if (0 === $maxChunks) {
            return '';
        }

        if (0 === $maxSentences) {
            return '';
        }

        if (0 === $maxWords) {
            return '';
        }

        $words = self::getRandomStartingWords($chain);

        /** @var array<string, bool> $endsOfSentencesMemo */
        $endsOfSentencesMemo = [];

        $chunkCount = 0;
        $sentenceCount = 0;
        $wordCount = 0;

        foreach ($words as $i => $word) {
            if ('' === $word) {
                $chunkCount++;
            } else {
                $wordCount++;
            }

            if (null !== $maxSentences && !isset($endsOfSentencesMemo[$word])) {
                $endsOfSentencesMemo[$word] = $this->endOfSentenceResolver->isEndOfSentence($word);
                if ($endsOfSentencesMemo[$word]) {
                    $sentenceCount++;
                }
            }

            $invalid = (null !== $maxChunks && $maxChunks <= $chunkCount)
                || (null !== $maxSentences && $maxSentences <= $sentenceCount)
                || (null !== $maxWords && $maxWords <= $wordCount);

            if ($invalid) {
                return self::toText(array_slice($words, 0, $i + 1), $chunkSeparator, $wordSeparator);
            }
        }

        $wordsInLink = $words;

        // memoize function results for speed

        /** @var array<string, array> $wordDataMemo */
        $wordDataMemo = [];
        $isLineBreak = false;

        // the main loop

        while (true) {
            // resolve the current state of the Markov chain from the cache
            $wordData = &$wordDataMemo;

            foreach ($wordsInLink as $wordInLink) {
                if (!isset($wordData[$wordInLink])) {
                    $wordData[$wordInLink] = [];
                }

                $wordData = &$wordData[$wordInLink];
            }

            // persist analysis of the chain to the cache
            if (empty($wordData)) {
                $wordData = $this->getWeightedWordListAndMax($chain->getFrequenciesBySequence(...$wordsInLink));
            }

            // use random number generator to determine the next sequence:
            $nextWord = $wordData[1] ? $wordData[0][mt_rand(0, $wordData[1])] : $wordData[0][0];

            array_shift($wordsInLink);

            // have we reached the sentence limit?
            if (null !== $maxSentences) {
                if (!isset($endsOfSentencesMemo[$nextWord])) {
                    $endsOfSentencesMemo[$nextWord] = $this->endOfSentenceResolver->isEndOfSentence($nextWord);
                }

                if ($endsOfSentencesMemo[$nextWord]) {
                    $sentenceCount++;
                }
            }

            $words[] = $nextWord;

            if (null !== $maxSentences && $maxSentences <= $sentenceCount) {
                break;
            }

            $wordsInLink[] = (string)$nextWord;

            if (null === $maxChunks && null === $maxWords) {
                continue;
            }

            if ($isLineBreak && '' === $nextWord) {
                // two line breaks in a row: let's increment the word count to prevent an infinite loop
                $wordCount++;
            } else {
                $isLineBreak = '' === $nextWord;
                if ($isLineBreak) {
                    $chunkCount++;
                } else {
                    $wordCount++;
                }
            }

            // have we reached the chunk limit?
            if ($maxChunks <= $chunkCount) {
                break;
            }

            if (null === $maxWords) {
                continue;
            }

            // word count exceeded:
            if ($maxWords <= $wordCount) {
                break;
            }

            // if we're getting nothing but whitespace and the word count isn't incrementing, break as well
            if ($chunkCount > $maxWords) {
                break;
            }
        }

        return self::toText($words, $chunkSeparator, $wordSeparator);
    }

    /**
     * @param mixed $options
     * @return TextGeneratorOptionsInterface
     */
    private static function buildOptions($options): TextGeneratorOptionsInterface
    {
        if ($options instanceof TextGeneratorOptionsInterface) {
            return $options;
        }

        if (null === $options || is_array($options)) {
            $options = is_array($options) ? TextGeneratorOptions::fromArray($options) : new TextGeneratorOptions();
            return $options;
        }

        throw TypeException::fromVariable(
            'options',
            sprintf('array, NULL, or instance of %s', TextGeneratorOptionsInterface::class),
            $options
        );
    }

    /**
     * @param TextGeneratorOptionsInterface $options
     * @return array<int, int|null>
     */
    private static function getMaximumChunksSentencesAndWords(TextGeneratorOptionsInterface $options): array
    {
        $maxChunks = $options->getMaxChunks();
        $maxSentences = $options->getMaxSentences();
        $maxWords = $options->getMaxWords();

        if (null !== $maxChunks && $maxChunks < 0) {
            throw new InvalidArgumentException('Maximum chunks must be NULL or greater than -1');
        }

        if (null !== $maxSentences && $maxSentences < 0) {
            throw new InvalidArgumentException('Maximum sentences must be NULL or greater than -1');
        }

        if (null !== $maxWords && $maxWords < 0) {
            throw new InvalidArgumentException('Maximum words must be NULL or greater than -1');
        }

        // if no arguments passed, set maximum chunks to 1 as a default
        if (null === $maxChunks && null === $maxSentences && null === $maxWords) {
            $maxChunks = 1;
        }

        return [$maxChunks, $maxSentences, $maxWords];
    }

    /**
     * Resolves a random starting point in the chain
     * @param ChainInterface $chain The Markov chain
     * @return string[] The first generated words in the output
     */
    private static function getRandomStartingWords(ChainInterface $chain): array
    {
        $elements = $chain->getPossibleStartingSequences();

        if (0 === count($elements)) {
            throw new InvalidArgumentException('Cannot generate text; Markov chain has no starting point');
        }

        $startingWords = $elements[mt_rand(0, count($elements) - 1)] ?? [];
        if (0 === count($startingWords)) {
            throw new InvalidArgumentException('Cannot generate text; starting words are empty');
        }

        return array_map('strval', $startingWords);
    }

    /**
     * Concatenates the generated words to yield the output string
     * @param string[] $words Generated words
     * @param string $chunkSeparator "Glue" for joining paragraphs
     * @param string $wordSeparator "Glue" for joining words in sentences
     * @return string The final output
     */
    private static function toText(array $words, string $chunkSeparator, string $wordSeparator): string
    {
        $inSentence = false;
        $sentenceCount = 0;
        $wordsBySentence = [];

        foreach ($words as $word) {
            $isLineBreak = '' === $word;

            if ($isLineBreak && $inSentence) {
                $sentenceCount++;
                $inSentence = false;
            } elseif (!$isLineBreak && isset($wordsBySentence[$sentenceCount])) {
                $wordsBySentence[$sentenceCount][] = $word;
                $inSentence = true;
            } elseif (!$isLineBreak) {
                $wordsBySentence[$sentenceCount] = [$word];
                $inSentence = true;
            }
        }

        $sentences = array_map(function ($wordsInSentence) use ($wordSeparator): string {
            return implode($wordSeparator, $wordsInSentence);
        }, $wordsBySentence);

        return trim(implode($chunkSeparator, $sentences));
    }

    /**
     * Analyzes word frequencies of a Markov Chain sequences and yields an array from which to select a random element
     * @param array<string, int> $frequencies Frequencies of the next possible state in the chain
     * @return array<int, array|int> An array that looks like [["state1", "state1", "state2"], 2], where 2 is the count
     * of possible states minus one
     */
    private function getWeightedWordListAndMax(array $frequencies): array
    {
        if (1 === count($frequencies) && current($frequencies) > 0) {
            return [[key($frequencies)], 0];
        }

        $frequencies = $this->arrayUtilities->filterOutIntegersLessThanOne($frequencies);

        $wordCount = count($frequencies);
        if (0 === $wordCount) {
            throw new RuntimeException('Cannot generate text; cannot find the next word in the chain');
        }

        if (array_sum($frequencies) === $wordCount) {
            return [array_keys($frequencies), $wordCount - 1];
        }

        $frequencies = $this->arrayUtilities->divideValuesByGreatestCommonFactor($frequencies);

        $weightedWordList = array_merge(...array_map('array_fill',
            array_fill(0, $wordCount, 0),
            $frequencies,
            array_keys($frequencies)
        ));

        return [$weightedWordList, count($weightedWordList) - 1];
    }
}
