<?php

declare(strict_types=1);

namespace CliffordVickrey\Malarkey\MarkovChain;

class Link implements LinkInterface
{
    /** @var array<string, int> */
    private $frequencies;
    /** @var WordInterface[] */
    private $words;

    /**
     * Link constructor.
     * @param array<string, int> $frequencies
     * @param WordInterface ...$words
     */
    public function __construct(array $frequencies, WordInterface ...$words)
    {
        $this->frequencies = $frequencies;
        $this->words = $words;
    }

    /**
     * (@inheritDoc)
     */
    public function getStateFrequencies(): array
    {
        return $this->frequencies;
    }

    /**
     * (@inheritDoc)
     */
    public function getWords(): array
    {
        return $this->words;
    }
}
