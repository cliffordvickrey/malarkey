<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

class EndOfSentenceResolver implements EndOfSentenceResolverInterface
{
    /**
     * (@inheritDoc)
     */
    public function isEndOfSentence(string $word): bool
    {
        return (bool)preg_match('/[.!?]([\'’"”»)]*)$/', $word);
    }
}