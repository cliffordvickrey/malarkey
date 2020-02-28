<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

use CliffordVickrey\Malarkey\Exception\RuntimeException;
use function array_filter;
use function get_object_vars;
use function implode;
use function sprintf;

final class ChainBuilder
{
    /** @var array<int, array> */
    private $frequenciesTable;
    /** @var array<string, array> */
    private $frequenciesTree;
    /** @var int */
    private $lookBehind;
    /** @var array<int, array<int, string>> */
    private $possibleStartingSequences;

    /**
     * @return array<int, array>
     */
    public function getFrequenciesTable(): array
    {
        return $this->frequenciesTable;
    }

    /**
     * @param array<int, array> $frequenciesTable
     */
    public function setFrequenciesTable(array $frequenciesTable): void
    {
        $this->frequenciesTable = $frequenciesTable;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFrequenciesTree(): array
    {
        return $this->frequenciesTree;
    }

    /**
     * @param array<string, mixed> $frequenciesTree
     */
    public function setFrequenciesTree(array $frequenciesTree): void
    {
        $this->frequenciesTree = $frequenciesTree;
    }

    /**
     * @return int
     */
    public function getLookBehind(): int
    {
        return $this->lookBehind;
    }

    /**
     * @param int $lookBehind
     */
    public function setLookBehind(int $lookBehind): void
    {
        $this->lookBehind = $lookBehind;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function getPossibleStartingSequences(): array
    {
        return $this->possibleStartingSequences;
    }

    /**
     * @param array<int, array<int, string>> $possibleStartingSequences
     */
    public function setPossibleStartingSequences(array $possibleStartingSequences): void
    {
        $this->possibleStartingSequences = $possibleStartingSequences;
    }

    public function validate(): void
    {
        $nullProperties = array_filter(get_object_vars($this), 'is_null');
        if (!empty($nullProperties)) {
            throw new RuntimeException(sprintf(
                '%s is invalid; properties %s are NULL',
                static::class,
                implode(', ', array_keys($nullProperties))
            ));
        }
    }
}
