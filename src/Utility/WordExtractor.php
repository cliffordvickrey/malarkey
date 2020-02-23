<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

use function array_merge;
use function explode;
use function preg_match;
use function preg_replace;
use function str_replace;
use function trim;

class WordExtractor implements WordExtractorInterface
{
    /**
     * (@inheritDoc)
     */
    public function extractWords(string $text): array
    {
        $whiteSpaceNormalized = str_replace(["\t", "\0", "\x0B"], ' ', $text);
        $whiteSpaceNormalized = preg_replace('/( *)[\n\r]+( *)/', "\n", $whiteSpaceNormalized);
        $whiteSpaceNormalized = preg_replace('/(\s)+/', '$1', $whiteSpaceNormalized);

        $hasTrailingLineBreak = (bool)preg_match('/\n$/', $whiteSpaceNormalized);

        $whiteSpaceNormalized = trim($whiteSpaceNormalized);

        if ('' === $whiteSpaceNormalized) {
            return [];
        }

        $paragraphs = explode("\n", $whiteSpaceNormalized);

        $words = [];
        foreach ($paragraphs as $i => $paragraph) {
            if ($i) {
                $words[] = '';
            }
            $words = array_merge($words, explode(' ', $paragraph));
        }

        if ($hasTrailingLineBreak) {
            $words[] = '';
        }

        return $words;
    }
}
