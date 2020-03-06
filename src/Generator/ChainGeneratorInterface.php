<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;

interface ChainGeneratorInterface
{
    /**
     * Generates a Markov chain of word occurrences
     * @param string $text The source text
     * @param int $lookBehind The number of words to look behind when resolving the next sequence in the Markov chain.
     * The higher the number, the more coherent will be the randomly-generated text
     * @return ChainInterface The generated chain
     */
    public function generateChain(string $text, int $lookBehind = 2);
}