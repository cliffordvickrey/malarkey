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
    public function generateChain(string $text, int $coherence = 2, bool $ignoreLineBreaks = false): ChainInterface
    {
        if ($coherence < 1) {
            throw new InvalidArgumentException('Coherence cannot be less than 1');
        }

        $words = $this->extractWords($text, $ignoreLineBreaks);
        $dictionary = $this->getDictionary($words);

        $hashes = [];
        $hashedFrequencies = self::getHashedFrequencies($words, $coherence, $hashes);

        $wordExtractor = function (string $word) use ($dictionary): Word {
            return $dictionary[$word];
        };

        $links = [];

        foreach ($hashedFrequencies as $hash => $frequencies) {
            $links[] = new Link($frequencies, ...array_map($wordExtractor, $hashes[$hash]));
        }

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
                $startOfSentence = true;
            } elseif ('' === $word || !$endOfSentence) {
                $startOfSentence = false;
            } elseif (isset($lowerCaseWordsMap[$word]) && !$lowerCaseWordsMap[$word]) {
                $startOfSentence = true;
            } elseif (isset($lowerCaseWordsMap[$word]) && $lowerCaseWordsMap[$word]) {
                $startOfSentence = false;
            } else {
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
     * @param int $coherence
     * @param array<string, array<int, string>> $hashes
     * @return array<string, array<string, int>>
     */
    private static function getHashedFrequencies(array $words, int $coherence, array &$hashes): array
    {
        if (count($words) < $coherence) {
            throw new InvalidArgumentException('Coherence cannot be greater than the number of words in the text');
        }

        $wordsInLink = array_slice($words, 0, $coherence);
        $words = array_merge(array_slice($words, $coherence), $wordsInLink);

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
