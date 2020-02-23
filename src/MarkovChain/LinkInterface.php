<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

/**
 * Encapsulates a given "state" of the Markov chain
 */
interface LinkInterface
{
    /**
     * Returns the frequencies of the next word occurrences in the chain
     * @return array<string, int> An array of words (keys) and frequencies (values)
     */
    public function getStateFrequencies(): array;

    /**
     * Returns the words in he Markov chain state
     * @return WordInterface[]
     */
    public function getWords(): array;
}