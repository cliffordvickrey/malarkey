<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\Generator;

use CliffordVickrey\Malarkey\Exception\InvalidArgumentException;
use function is_numeric;
use function is_scalar;

class TextGeneratorOptions implements TextGeneratorOptionsInterface
{
    /** @var string */
    private $chunkSeparator = "\n\n";
    /** @var int|null */
    private $maxChunks;
    /** @var int|null */
    private $maxSentences;
    /** @var int|null */
    private $maxWords;
    /** @var string */
    private $wordSeparator = ' ';

    /**
     * @param array<string, mixed> $options
     * @return TextGeneratorOptions
     */
    public static function fromArray(array $options): TextGeneratorOptions
    {
        $chunkSeparator = $options['chunkSeparator'] ?? null;
        $maxChunks = $options['maxChunks'] ?? null;
        $maxSentences = $options['maxSentences'] ?? null;
        $maxWords = $options['maxWords'] ?? null;
        $wordSeparator = $options['wordSeparator'] ?? null;

        $self = new self();

        if (is_scalar($chunkSeparator)) {
            $self->chunkSeparator = (string)$chunkSeparator;
        } elseif (null !== $chunkSeparator) {
            throw new InvalidArgumentException('Invalid value for option "chunkSeparator"');
        }

        if (is_numeric($maxChunks)) {
            $self->maxChunks = (int)$maxChunks;
        } elseif (null !== $maxChunks) {
            throw new InvalidArgumentException('Invalid value for option "maxChunks"');
        }

        if (is_numeric($maxSentences)) {
            $self->maxSentences = (int)$maxSentences;
        } elseif (null !== $maxSentences) {
            throw new InvalidArgumentException('Invalid value for option "maxSentences"');
        }

        if (is_numeric($maxWords)) {
            $self->maxWords = (int)$maxWords;
        } elseif (null !== $maxWords) {
            throw new InvalidArgumentException('Invalid value for option "maxWords"');
        }

        if (is_scalar($wordSeparator)) {
            $self->wordSeparator = (string)$wordSeparator;
        } elseif (null !== $wordSeparator) {
            throw new InvalidArgumentException('Invalid value for option "wordSeparator"');
        }

        return $self;
    }

    /**
     * (@inheritDoc)
     */
    public function getChunkSeparator(): string
    {
        return $this->chunkSeparator;
    }

    /**
     * @param string $chunkSeparator
     */
    public function setChunkSeparator(string $chunkSeparator): void
    {
        $this->chunkSeparator = $chunkSeparator;
    }

    /**
     * (@inheritDoc)
     */
    public function getMaxChunks(): ?int
    {
        return $this->maxChunks;
    }

    /**
     * @param int|null $maxChunks
     */
    public function setMaxChunks(?int $maxChunks): void
    {
        $this->maxChunks = $maxChunks;
    }

    /**
     * (@inheritDoc)
     */
    public function getMaxWords(): ?int
    {
        return $this->maxWords;
    }

    /**
     * @param int|null $maxWords
     */
    public function setMaxWords(?int $maxWords): void
    {
        $this->maxWords = $maxWords;
    }

    /**
     * (@inheritDoc)
     */
    public function getMaxSentences(): ?int
    {
        return $this->maxSentences;
    }

    /**
     * @param int|null $maxSentences
     */
    public function setMaxSentences(?int $maxSentences): void
    {
        $this->maxSentences = $maxSentences;
    }

    /**
     * (@inheritDoc)
     */
    public function getWordSeparator(): string
    {
        return $this->wordSeparator;
    }

    /**
     * @param string $wordSeparator
     */
    public function setWordSeparator(string $wordSeparator): void
    {
        $this->wordSeparator = $wordSeparator;
    }
}
