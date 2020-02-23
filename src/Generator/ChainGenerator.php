<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Exception\InvalidArgumentException;
use CliffordVickrey\Malarkey\MarkovChain\Chain;
use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;
use CliffordVickrey\Malarkey\MarkovChain\ChainWithoutJsonSupport;
use CliffordVickrey\Malarkey\MarkovChain\Link;
use CliffordVickrey\Malarkey\MarkovChain\Word;
use CliffordVickrey\Malarkey\Utility\EndOfSentenceResolver;
use CliffordVickrey\Malarkey\Utility\EndOfSentenceResolverInterface;
use CliffordVickrey\Malarkey\Utility\LowerCaseResolver;
use CliffordVickrey\Malarkey\Utility\LowerCaseResolverInterface;
use CliffordVickrey\Malarkey\Utility\WordExtractor;
use CliffordVickrey\Malarkey\Utility\WordExtractorInterface;
use function array_filter;
use function array_map;
use function array_merge;
use function array_shift;
use function array_slice;
use function array_values;
use function count;
use function interface_exists;
use function serialize;

/**
 * Markov chain generator. Takes text and builds a stochastic model describing the flow of the words therein.
 */
class ChainGenerator implements ChainGeneratorInterface
{
    /** @var EndOfSentenceResolverInterface */
    private $endOfSentenceResolver;
    /** @var LowerCaseResolverInterface */
    private $lowerCaseResolver;
    /** @var WordExtractorInterface */
    private $wordExtractor;
    /** @var bool */
    private $jsonSupport;

    /**
     * ChainGenerator constructor.
     * @param EndOfSentenceResolverInterface|null $endOfSentenceResolver
     * @param WordExtractorInterface|null $wordExtractor
     */
    public function __construct(
        ?EndOfSentenceResolverInterface $endOfSentenceResolver = null,
        ?WordExtractorInterface $wordExtractor = null
    )
    {
        $this->endOfSentenceResolver = $endOfSentenceResolver ?? new EndOfSentenceResolver();
        $this->lowerCaseResolver = new LowerCaseResolver();
        $this->wordExtractor = $wordExtractor ?? new WordExtractor();
        $this->jsonSupport = interface_exists('JsonSerializable');
    }

    /**
     * (@inheritDoc)
     */
    public function generateChain(string $text, int $lookBehind = 2, bool $ignoreLineBreaks = false): ChainInterface
    {
        if ($lookBehind < 1) {
            throw new InvalidArgumentException('LookBehind cannot be less than 1');
        }

        // create a list of words from the source text
        $words = $this->extractWords($text, $ignoreLineBreaks);

        // get information about every word. Are they starts to or ends of sentences?
        $dictionary = $this->getDictionary($words);

        // for every possible state of the chain, determine the likelihoods of the next word in the sequence. Each hash
        // represents a unique possible state of the chain
        $hashes = [];
        $hashedFrequencies = self::getHashedFrequencies($words, $lookBehind, $hashes);

        $wordExtractor = function (string $word) use ($dictionary): Word {
            return $dictionary[$word];
        };

        $links = [];

        // encapsulate all the word and frequency data in Link objects
        foreach ($hashedFrequencies as $hash => $frequencies) {
            $links[] = new Link($frequencies, ...array_map($wordExtractor, $hashes[$hash]));
        }

        // if ext-json wasn't built with the running PHP instance, return a Chain object that doesn't implement
        // \JsonSerializable
        if (!$this->jsonSupport) {
            return new ChainWithoutJsonSupport($links);
        }

        return new Chain($links);
    }

    /**
     * @param string $text
     * @param bool $ignoreLineBreaks
     * @return string[]
     */
    private function extractWords(string $text, bool $ignoreLineBreaks): array
    {
        $words = $this->wordExtractor->extractWords($text);

        if (empty($words)) {
            return [''];
        }

        if ($ignoreLineBreaks) {
            return array_values(array_filter($words, function (string $word): bool {
                return '' !== $word;
            }));
        }

        return $words;
    }

    /**
     * @param string[] $words
     * @return array<string, Word>
     */
    private function getDictionary(array $words): array
    {
        $k = count($words) - 1;

        $endOfSentence = false;

        $lowerCaseWordsMap = [];
        $startsOfSentencesMap = [];
        $endOfSentencesMap = [];
        $endsOfSentences = [];

        foreach ($words as $i => $word) {
            if (!$i) {
                // start of the chain: always a valid start of a sentence
                $startOfSentence = true;
            } elseif ('' === $word || !$endOfSentence) {
                // paragraph break or not the word immediately ending a sentence: not a start of a sentence
                $startOfSentence = false;
            } elseif (isset($lowerCaseWordsMap[$word]) && !$lowerCaseWordsMap[$word]) {
                // non-lowercase word after the end of a sentence: start of a sentence
                $startOfSentence = true;
            } elseif (isset($lowerCaseWordsMap[$word]) && $lowerCaseWordsMap[$word]) {
                // lowercase word after the end of a sentence: not start of a sentence
                $startOfSentence = false;
            } else {
                // determine whether the word is lowercase
                $lowerCaseWordsMap[$word] = $this->lowerCaseResolver->isWordLowerCase($word);
                $startOfSentence = !$lowerCaseWordsMap[$word];
            }

            if (!isset($startsOfSentencesMap[$word]) || (!$startsOfSentencesMap[$word] && $startOfSentence)) {
                $startsOfSentencesMap[$word] = $startOfSentence;
            }

            $endOfSentence = $endOfSentencesMap[$word] ?? null;

            if (null === $endOfSentence) {
                if ($i < $k || !empty($endsOfSentences)) {
                    $endOfSentence = $this->endOfSentenceResolver->isEndOfSentence($word);
                } else {
                    // we've reached the end of the chain without finding an end of sentence marker. Let's mark the end
                    // of the chain as an end of sentence
                    $endOfSentence = true;
                }

                $endOfSentencesMap[$word] = $endOfSentence;

                if ($endOfSentence) {
                    $endsOfSentences[$word] = true;
                }
            }
        }

        $dictionary = [];

        foreach ($startsOfSentencesMap as $word => $startOfSentence) {
            $dictionary[$word] = new Word((string)$word, $startOfSentence, $endOfSentencesMap[$word]);
        }

        return $dictionary;
    }

    /**
     * @param string[] $words
     * @param int $lookBehind
     * @param array<string, array<int, string>> $hashes
     * @return array<string, array<string, int>>
     */
    private static function getHashedFrequencies(array $words, int $lookBehind, array &$hashes): array
    {
        if (count($words) < $lookBehind) {
            throw new InvalidArgumentException('LookBehind cannot be greater than the number of words in the text');
        }

        // ensure that the end of the chain is linked to the beginning
        $wordsInLink = array_slice($words, 0, $lookBehind);
        $words = array_merge(array_slice($words, $lookBehind), $wordsInLink);

        $hash = serialize($wordsInLink);
        $frequencies = [];

        foreach ($words as $word) {
            if (!isset($frequencies[$hash])) {
                $frequencies[$hash] = [];
            }

            if (!isset($frequencies[$hash][$word])) {
                $frequencies[$hash][$word] = 1;
            } else {
                $frequencies[$hash][$word]++;
            }

            array_shift($wordsInLink);
            $wordsInLink[] = $word;
            $hash = serialize($wordsInLink);
            $hashes[$hash] = $wordsInLink;
        }

        return $frequencies;
    }
}
