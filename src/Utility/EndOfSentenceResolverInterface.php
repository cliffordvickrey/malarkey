<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

interface EndOfSentenceResolverInterface
{
    public function isEndOfSentence(string $word): bool;
}