<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

use CliffordVickrey\Malarkey\Exception\InvalidArgumentException;
use CliffordVickrey\Malarkey\Exception\OutOfBoundsException;
use CliffordVickrey\Malarkey\Exception\TypeException;
use Serializable;
use function count;
use function implode;
use function is_array;
use function is_int;
use function serialize;
use function sprintf;
use function unserialize;

abstract class ChainAbstract implements ChainInterface, Serializable
{
    /** @var int */
    private $lookBack = 0;
    /** @var array<int, array> */
    private $chain = [];
    /** @var array<string, array> */
    private $frequencies = [];
    /** @var array<int, array<int, string>> */
    private $startingWordSequences = [];
    /** @var array<string, bool> */
    private $endsOfSentencesMap = [];

    /**
     * Chain constructor.
     * @param LinkInterface[] $links
     */
    public function __construct(array $links)
    {
        $dictionary = [];

        foreach ($links as $link) {
            $this->bindLinkToChain($link, $dictionary);
        }

        if ($this->lookBack < 1) {
            throw new InvalidArgumentException('Expected at least one link in the Markov chain');
        }
    }

    /**
     * @param LinkInterface $link
     * @param array<string, array<string, bool>> $dictionary
     */
    private function bindLinkToChain(LinkInterface $link, array &$dictionary): void
    {
        $words = $link->getWords();

        $count = count($words);

        if (0 === $count) {
            throw new InvalidArgumentException('Link must have at least one word');
        }

        if (!$this->lookBack) {
            $this->lookBack = $count;
        } elseif ($count !== $this->lookBack) {
            throw new InvalidArgumentException(
                sprintf('Expected link to have %d word(s); got %d', $this->lookBack, $count)
            );
        }

        $frequencies = $link->getStateFrequencies();

        if (0 === count($frequencies)) {
            throw new InvalidArgumentException('Link frequencies cannot be empty');
        }

        $ref = &$this->frequencies;
        $wordValues = [];

        foreach ($words as $word) {
            $key = (string)$word;

            if (!isset($ref[$key])) {
                $ref[$key] = [];
            }

            $ref = &$ref[$key];

            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [$word->isStartOfSentence(), $word->isEndOfSentence()];

                if ($dictionary[$key][1]) {
                    $this->endsOfSentencesMap[$key] = true;
                }
            }

            $wordValues[] = $key;
        }

        if (!empty($ref)) {
            throw new InvalidArgumentException(sprintf(
                'Link with word values "%s" is not unique to the chain',
                implode(', ', $words)
            ));
        }

        $ref = $link->getStateFrequencies();

        if ($dictionary[$wordValues[0]][0]) {
            $this->startingWordSequences[] = $wordValues;
        }

        $this->chain[] = [
            'words' => $wordValues,
            'frequencies' => $frequencies
        ];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'lookBack' => $this->lookBack,
            'chain' => $this->chain,
            'frequencies' => $this->frequencies,
            'startingWordSequences' => $this->startingWordSequences,
            'endsOfSentencesMap' => $this->endsOfSentencesMap
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function jsonSerialize()
    {
        return $this->chain;
    }

    /**
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        $unSerialized = unserialize($serialized);

        $lookBack = $unSerialized['lookBack'] ?? null;
        if (!is_int($lookBack)) {
            throw TypeException::fromVariable('lookBack', 'int', $lookBack);
        }

        $chain = $unSerialized['chain'] ?? null;
        if (!is_array($chain)) {
            throw TypeException::fromVariable('chain', 'array', $chain);
        }

        $frequencies = $unSerialized['frequencies'] ?? null;
        if (!is_array($frequencies)) {
            throw TypeException::fromVariable('frequencies', 'array', $frequencies);
        }

        $startingWordSequences = $unSerialized['startingWordSequences'] ?? null;
        if (!is_array($startingWordSequences)) {
            throw TypeException::fromVariable('startingWordSequences', 'array', $startingWordSequences);
        }

        $endsOfSentencesMap = $unSerialized['endsOfSentencesMap'] ?? null;
        if (!is_array($endsOfSentencesMap)) {
            throw TypeException::fromVariable('endsOfSentencesMap', 'array', $endsOfSentencesMap);
        }

        $this->lookBack = $lookBack;
        $this->chain = $chain;
        $this->frequencies = $frequencies;
        $this->startingWordSequences = $startingWordSequences;
        $this->endsOfSentencesMap = $endsOfSentencesMap;
    }

    /**
     * (@inheritDoc)
     */
    public function getStartingWordSequences(): array
    {
        return $this->startingWordSequences;
    }

    /**
     * (@inheritDoc)
     */
    public function getStateFrequencies(string ...$words): array
    {
        $frequencies = null;
        $i = 0;

        foreach ($words as $i => $word) {
            if (isset($frequencies)) {
                $frequencies = $frequencies[$word] ?? [];
                continue;
            }

            $frequencies = $this->frequencies[$word] ?? null;

            if (!isset($frequencies)) {
                break;
            }
        }

        if (!empty($frequencies) && $i === ($this->lookBack - 1)) {
            return $frequencies;
        }

        throw new OutOfBoundsException(sprintf('Word combination "%s" not found in chain', implode(', ', $words)));
    }

    /**
     * (@inheritDoc)
     */
    public function isEndOfSentence(string $word): bool
    {
        return isset($this->endsOfSentencesMap[$word]);
    }

    /**
     * (@inheritDoc)
     */
    public function getLookBack(): int
    {
        return $this->lookBack;
    }
}
