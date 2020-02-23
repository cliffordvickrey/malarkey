<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

class Word implements WordInterface
{
    /** @var string */
    private $value;
    /** @var bool */
    private $startOfSentence;
    /** @var bool */
    private $endOfSentence;

    /**
     * Word constructor.
     * @param string $value
     * @param bool $startOfSentence
     * @param bool $endOfSentence
     */
    public function __construct(string $value, bool $startOfSentence = false, bool $endOfSentence = false)
    {
        $this->value = $value;
        $this->startOfSentence = $startOfSentence;
        $this->endOfSentence = $endOfSentence;
    }

    /**
     * (@inheritDoc)
     */
    public function isStartOfSentence(): bool
    {
        return $this->startOfSentence;
    }

    /**
     * (@inheritDoc)
     */
    public function isEndOfSentence(): bool
    {
        return $this->endOfSentence;
    }

    /**
     * (@inheritDoc)
     */
    public function __toString()
    {
        return $this->value;
    }
}
