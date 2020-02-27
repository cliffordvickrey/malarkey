<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;

interface TextGeneratorInterface
{
    /**
     * @param ChainInterface $chain The Markov chain representation of text
     * @param array<string, mixed>|TextGeneratorOptionsInterface|null $options
     * @return string
     */
    public function generateText(ChainInterface $chain, $options = null): string;
}
