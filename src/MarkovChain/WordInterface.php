<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

interface WordInterface
{
    /**
     * Converts the word object to a scalar variable
     * @return string
     */
    public function __toString();

    /**
     * Returns whether or not the word can be a start of a sentence
     * @return bool
     */
    public function isStartOfSentence(): bool;

    /**
     * Returns whether or not the word can be an end to a sentence
     * @return bool
     */
    public function isEndOfSentence(): bool;
}