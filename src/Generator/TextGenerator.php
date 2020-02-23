<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Exception\InvalidArgumentException;
use CliffordVickrey\Malarkey\Exception\RuntimeException;
use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;
use CliffordVickrey\Malarkey\Utility\ArrayUtilities;
use function array_fill;
use function array_keys;
use function array_map;
use function array_shift;
use function array_slice;
use function array_sum;
use function call_user_func_array;
use function count;
use function current;
use function implode;
use function key;
use function mt_rand;

/**
 * Takes a Markov chain object to produce random text
 */
class TextGenerator implements TextGeneratorInterface
{
    /** @var ArrayUtilities */
    private $arrayUtilities;

    public function __construct()
    {
        $this->arrayUtilities = new ArrayUtilities();
    }

    /**
     * (@inheritDoc)
     */
    public function generateText(
        ChainInterface $chain,
        ?int $maxSentences,
        ?int $maxWords = null,
        string $wordSeparator = ' ',
        string $paragraphSeparator = "\n\n"
    ): string
    {
        // validate parameters:

        self::assertValidParameters($maxSentences, $maxWords);

        // set up initial state:

        if (0 === $maxSentences) {
            return '';
        }

        if (0 === $maxWords) {
            return '';
        }

        $words = self::getRandomStartingWords($chain);

        $paragraphCount = 0;
        $sentenceCount = 0;
        $wordCount = 0;

        foreach ($words as $i => $word) {
            if ('' === $word) {
                $paragraphCount++;
            } else {
                $wordCount++;
            }

            if ($chain->isEndOfSentence($word)) {
                $sentenceCount++;
            }

            if (null !== $maxWords && $maxWords <= $wordCount) {
                return self::toText(array_slice($words, 0, $maxWords), $wordSeparator, $paragraphSeparator);
            }

            if (null !== $maxSentences && $maxSentences <= $sentenceCount) {
                return self::toText(array_slice($words, 0, $i + 1), $wordSeparator, $paragraphSeparator);
            }
        }

        $wordsInLink = $words;

        // memoize function results for speed

        /** @var array<string, bool> $endOfSentenceMemo */
        $endOfSentenceMemo = [];
        /** @var array<string, array> $wordDataMemo */
        $wordDataMemo = [];

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
                $wordData = $this->getWeightedWordListAndMax($chain->getStateFrequencies(...$wordsInLink));
            }

            // use random number generator to determine the next sequence:
            $nextWord = $wordData[1] ? $wordData[0][mt_rand(0, $wordData[1])] : $wordData[0][0];

            array_shift($wordsInLink);

            // have we reached the sentence limit?
            if (!isset($endOfSentenceMemo[$nextWord])) {
                $endOfSentenceMemo[$nextWord] = $chain->isEndOfSentence((string)$nextWord);
            }

            if ($endOfSentenceMemo[$nextWord]) {
                $sentenceCount++;
            }

            $words[] = $nextWord;

            if (null !== $maxSentences && $maxSentences <= $sentenceCount) {
                break;
            }

            $wordsInLink[] = (string)$nextWord;

            if ('' === $nextWord) {
                $paragraphCount++;
            } else {
                $wordCount++;
            }

            if (null === $maxWords) {
                continue;
            }

            // word count exceeded:
            if ($maxWords <= $wordCount) {
                break;
            }

            // if we're getting nothing but whitespace and the word count isn't incrementing, break as well
            if ($paragraphCount > $maxWords) {
                break;
            }
        }

        return self::toText($words, $wordSeparator, $paragraphSeparator);
    }

    /**
     * Throw a logic exception if invalid max sentence or word values passed to generateText
     * @param int|null $maxSentences
     * @param int|null $maxWords
     */
    private static function assertValidParameters(?int $maxSentences, ?int $maxWords): void
    {
        if (null !== $maxSentences && $maxSentences < 0) {
            throw new InvalidArgumentException('Maximum sentences must be NULL or greater than -1');
        }

        if (null !== $maxWords && $maxWords < 0) {
            throw new InvalidArgumentException('Maximum words must be NULL or greater than -1');
        }

        if (null === $maxSentences && null === $maxWords) {
            throw new InvalidArgumentException('Maximum sentences and maximum words cannot both be NULL');
        }
    }

    /**
     * Resolves a random starting point in the chain
     * @param ChainInterface $chain The Markov chain
     * @return string[] The first generated words in the output
     */
    private static function getRandomStartingWords(ChainInterface $chain): array
    {
        $elements = $chain->getStartingWordSequences();

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
     * @param string $wordSeparator "Glue" for joining words in sentences
     * @param string $paragraphSeparator "Glue" for joining paragraphs
     * @return string The final output
     */
    private static function toText(array $words, string $wordSeparator, string $paragraphSeparator): string
    {
        $inSentence = false;
        $sentenceCount = 0;
        $wordsBySentence = [];

        foreach ($words as $word) {
            $isParagraphBreak = '' === $word;

            if ($isParagraphBreak && $inSentence) {
                $sentenceCount++;
                $inSentence = false;
            } elseif (!$isParagraphBreak && isset($wordsBySentence[$sentenceCount])) {
                $wordsBySentence[$sentenceCount][] = $word;
                $inSentence = true;
            } elseif (!$isParagraphBreak) {
                $wordsBySentence[$sentenceCount] = [$word];
                $inSentence = true;
            }
        }

        $sentences = array_map(function ($wordsInSentence) use ($wordSeparator): string {
            return implode($wordSeparator, $wordsInSentence);
        }, $wordsBySentence);

        return trim(implode($paragraphSeparator, $sentences));
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

        $weightedWordList = call_user_func_array('array_merge', array_map('array_fill',
            array_fill(0, $wordCount, 0),
            $frequencies,
            array_keys($frequencies)
        ));

        return [$weightedWordList, count($weightedWordList) - 1];
    }
}
