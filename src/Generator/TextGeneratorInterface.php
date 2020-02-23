<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\MarkovChain\ChainInterface;

interface TextGeneratorInterface
{
    /**
     * @param ChainInterface $chain The Markov chain representation of text
     * @param int|null $maxSentences The maximum number of sentences to generate (before $maxWords is reached), or NULL
     * if unlimited
     * @param int|null $maxWords The maximum number of words to generate, or NULL if unlimited
     * @param string $wordSeparator String used to separate words in the output
     * @param string $paragraphSeparator String used to separate paragraphs in the output
     * @return string Randomly-generated but realistic seeming text
     */
    public function generateText(
        ChainInterface $chain,
        ?int $maxSentences,
        ?int $maxWords = null,
        string $wordSeparator = ' ',
        string $paragraphSeparator = "\n\n"
    ): string;
}
