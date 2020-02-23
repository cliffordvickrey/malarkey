<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

interface ChainInterface
{
    /**
     * Returns the number of words in a given state of a Markov chain. The higher the number, the more "coherent" the
     * text output will be
     * @return int
     */
    public function getCoherence(): int;

    /**
     * Returns the frequencies of the next word occurrences in the chain, by a given chain state.
     * @param string ...$words Words in the chain state
     * @return array<string, int> An array of words (keys) and frequencies (values)
     */
    public function getStateFrequencies(string ...$words): array;

    /**
     * Returns the possible starting states of the Markov chain
     * @return array<int, array<int, string>> A list of lists of words
     */
    public function getStartingWordSequences(): array;

    /**
     * Returns whether or not a given word in the Markov chain can be an end of a sentence
     * @param string $word
     * @return bool
     */
    public function isEndOfSentence(string $word): bool;
}
