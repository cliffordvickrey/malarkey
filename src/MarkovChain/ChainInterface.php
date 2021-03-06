<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

interface ChainInterface
{
    /**
     * Returns the number of words to look behind when resolving the next sequence in the Markov chain. The higher the
     * number, the more coherent will be the randomly-generated text
     * @return int
     */
    public function getLookBehind(): int;

    /**
     * Returns the frequencies of the next word occurrences in the chain, by a given chain state
     * @param string ...$words Words in the chain state
     * @return array<string, int> An array of words (keys) and frequencies (values)
     */
    public function getFrequenciesBySequence(string ...$words): array;

    /**
     * Returns the possible starting states of the Markov chain
     * @return array<int, array<int, string>> A list of lists of words
     */
    public function getPossibleStartingSequences(): array;
}
