<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

use CliffordVickrey\Malarkey\Exception\InvalidArgumentException;
use CliffordVickrey\Malarkey\Exception\OutOfBoundsException;
use CliffordVickrey\Malarkey\Exception\TypeException;
use Countable;
use Serializable;
use function count;
use function implode;
use function is_array;
use function is_int;
use function serialize;
use function sprintf;
use function unserialize;

abstract class ChainAbstract implements ChainInterface, Countable, Serializable
{
    /** @var array<string, array> */
    private $frequenciesTree = [];
    /** @var array<int, array> */
    private $frequenciesTable = [];
    /** @var int */
    private $lookBehind = 0;
    /** @var array<int, array<int, string>> */
    private $possibleStartingSequences = [];

    /**
     * ChainAbstract constructor.
     */
    final function __construct()
    {
    }

    /**
     * @param ChainBuilder $builder
     * @return ChainAbstract
     */
    public static function build(ChainBuilder $builder): ChainAbstract
    {
        $builder->validate();
        $static = new static();
        $static->frequenciesTree = $builder->getFrequenciesTree();
        $static->frequenciesTable = $builder->getFrequenciesTable();
        $static->lookBehind = $builder->getLookBehind();
        $static->possibleStartingSequences = $builder->getPossibleStartingSequences();
        return $static;
    }

    /**
     * @param string[] $words
     * @param array<string, int> $frequencies
     * @param bool|null $startOfSequence
     */
    public function add(array $words, array $frequencies, bool $startOfSequence = null): void
    {
        $count = count($words);

        if (0 === $count) {
            throw new InvalidArgumentException('Link must have at least one word');
        }

        if (!$this->lookBehind) {
            $this->lookBehind = $count;
        } elseif ($count !== $this->lookBehind) {
            throw new InvalidArgumentException(
                sprintf('Expected link to have %d word(s); got %d', $this->lookBehind, $count)
            );
        }

        if (0 === count($frequencies)) {
            throw new InvalidArgumentException('Link frequencies cannot be empty');
        }

        $ref = &$this->frequenciesTree;
        $wordValues = [];

        foreach ($words as $word) {
            $key = (string)$word;

            if (!isset($ref[$key])) {
                $ref[$key] = [];
            }

            $ref = &$ref[$key];

            $wordValues[] = $key;
        }

        if (!empty($ref)) {
            throw new InvalidArgumentException(sprintf(
                'Link with word values "%s" is not unique to the chain',
                implode(', ', $words)
            ));
        }

        if (true === $startOfSequence || (null === $startOfSequence && empty($this->possibleStartingSequences))) {
            $this->possibleStartingSequences[] = $wordValues;
        }

        $this->frequenciesTable[] = [
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
            'frequenciesTable' => $this->frequenciesTable,
            'frequenciesTree' => $this->frequenciesTree,
            'lookBehind' => $this->lookBehind,
            'possibleStartingSequences' => $this->possibleStartingSequences
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function jsonSerialize()
    {
        return $this->frequenciesTable;
    }

    /**
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        $unSerialized = unserialize($serialized);

        $frequenciesTable = $unSerialized['frequenciesTable'] ?? null;
        if (!is_array($frequenciesTable)) {
            throw TypeException::fromVariable('frequenciesTable', 'array', $frequenciesTable);
        }

        $frequenciesTree = $unSerialized['frequenciesTree'] ?? null;
        if (!is_array($frequenciesTree)) {
            throw TypeException::fromVariable('frequenciesTree', 'array', $frequenciesTree);
        }

        $lookBehind = $unSerialized['lookBehind'] ?? null;
        if (!is_int($lookBehind)) {
            throw TypeException::fromVariable('lookBehind', 'int', $lookBehind);
        }

        $possibleStartingSequences = $unSerialized['possibleStartingSequences'] ?? null;
        if (!is_array($possibleStartingSequences)) {
            throw TypeException::fromVariable('possibleStartingSequences', 'array', $possibleStartingSequences);
        }

        $this->frequenciesTree = $frequenciesTree;
        $this->frequenciesTable = $frequenciesTable;
        $this->lookBehind = $lookBehind;
        $this->possibleStartingSequences = $possibleStartingSequences;
    }

    /**
     * (@inheritDoc)
     */
    public function getPossibleStartingSequences(): array
    {
        return $this->possibleStartingSequences;
    }

    /**
     * (@inheritDoc)
     */
    public function getFrequenciesBySequence(string ...$words): array
    {
        $frequencies = null;
        $i = 0;

        foreach ($words as $i => $word) {
            if (isset($frequencies)) {
                $frequencies = $frequencies[$word] ?? [];
                continue;
            }

            $frequencies = $this->frequenciesTree[$word] ?? null;

            if (!isset($frequencies)) {
                break;
            }
        }

        if (!empty($frequencies) && $i === ($this->lookBehind - 1)) {
            return $frequencies;
        }

        throw new OutOfBoundsException(sprintf('Word combination "%s" not found in chain', implode(', ', $words)));
    }

    /**
     * (@inheritDoc)
     */
    public function getLookBehind(): int
    {
        return $this->lookBehind;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->frequenciesTable);
    }
}
