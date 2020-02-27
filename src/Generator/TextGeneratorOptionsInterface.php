<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

interface TextGeneratorOptionsInterface
{
    /**
     * "Glue" to concatenate words chunks emitted by the text generator
     * @return string
     */
    public function getChunkSeparator(): string;

    /**
     * Maximum number of chunks (paragraphs separated by line breaks) to generate
     * @return int|null
     */
    public function getMaxChunks(): ?int;

    /**
     * Maximum number of sentences to generate
     * @return int|null
     */
    public function getMaxSentences(): ?int;
    
    /**
     * Maximum number of words to generate
     * @return int|null
     */
    public function getMaxWords(): ?int;

    /**
     * "Glue" to concatenate words emitted by the text generator
     * @return string
     */
    public function getWordSeparator(): string;
}