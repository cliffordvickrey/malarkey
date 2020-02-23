<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

interface WordExtractorInterface
{
    /**
     * @param string $text
     * @return string[]
     */
    public function extractWords(string $text): array;
}