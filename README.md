# cliffordvickrey/malarkey

This package generates nonsensical but realistic-sounding text (malarkey!) using a simple Markov chain algorithm.

In a Markov chain system, all possible states are determined by previous states. In the context of text, it models the transition from one state ("hello") to a future state ("world!") using a set of fixed probabilities.

A Markov chain generator takes text and, for all sequences of words, models the likelihoods of the next word in the sequence. ("world!" might have a 75% chance of following "Hello," and "Nurse!" might have a 25% chance). It is straightforward to visit the chain and, following these probabilities, emit gibberish that mimics human writing.

## Requirements

* PHP 7.1 or higher

## Installation

Run the following to install this library:
```bash
$ composer require cliffordvickrey/malarkey
```

## Usage

```php
$text = "I'll buy that for a dollar! But I'll buy this for two dollars!";

$chainGenerator = new \CliffordVickrey\Malarkey\Generator\ChainGenerator();
$markovChain = $chainGenerator->generateChain($text);

$textGenerator = new \CliffordVickrey\Malarkey\Generator\TextGenerator();
$output = $textGenerator->generateText($markovChain, 1);

var_dump($output); // e.g. I'll by that for two dollars!

```

### ChainGenerator@generateChain
Generates a Markov chain from source text.

Arguments:
* `text` (string): The source text
* `coherence` (int): the number of words in a given state of a Markov chain. The higher the number, the more "coherent" to generated text. Defaults to 2
* `ignoreLineBreaks` (bool): Whether to skip over line breaks in the source text, such that no line breaks will appear in the generated output. Defaults to FALSE

### TextGenerator@generateText
Visits a Markov chain and returns randomly generated text.

Arguments:
* `chain` (ChainInterface): The object representing of a Markov Chain
* `maxSentences` (int|null): The maximum number of sentences to generate (before $maxWords is reached), or NULL if unlimited
* `maxWords` (int|null): The maximum number of words to generate, or NULL if unlimited
* `wordSeparator` (string): String used to separate words in the output. Defaults to " "
* `paragraphSeparator` (string): String used to paragraphs words in the output. Defaults to two newlines
