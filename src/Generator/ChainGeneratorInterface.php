<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;

interface ChainGeneratorInterface
{
    /**
     * Generates a Markov chain of word occurrences
     * @param string $text The source text
     * @param int $coherence The number of words describing the state of each link in the chain. The higher the value,
     * the more coherent the output text
     * @param bool $ignoreLineBreaks Whether or not to ignore line breaks & treat the source text as a single paragraph
     * @return ChainInterface The generated chain
     */
    public function generateChain(string $text, int $coherence = 2, bool $ignoreLineBreaks = false): ChainInterface;
}