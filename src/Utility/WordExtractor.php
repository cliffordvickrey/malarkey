<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Utility;

use function array_map;
use function array_merge;
use function array_pop;
use function array_reduce;
use function explode;
use function ltrim;
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
        $whiteSpaceNormalized = trim($whiteSpaceNormalized);

        if ('' === $whiteSpaceNormalized) {
            return [];
        }

        $chunks = explode("\n", $whiteSpaceNormalized);

        $words = array_reduce($chunks, function ($words, $chunk) {
            return array_merge($words, explode(' ', $chunk), ['']);
        }, []);

        array_pop($words);

        return $words;
    }
}
