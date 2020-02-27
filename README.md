# cliffordvickrey/malarkey

[![Build Status](https://travis-ci.com/cliffordvickrey/malarkey.svg?branch=master)](https://travis-ci.com/cliffordvickrey/malarkey/)
[![Coverage Status](https://coveralls.io/repos/github/cliffordvickrey/malarkey/badge.svg)](https://coveralls.io/github/cliffordvickrey/malarkey)

This package generates nonsensical but realistic-sounding text (malarkey!) using a simple Markov chain algorithm.

In a Markov chain system, all possible states are determined by previous states. In the context of text, it models the transition from one state ("hello") to a future state ("world!") using a set of fixed probabilities.

A Markov chain generator takes text and, for all sequences of words, models the likelihoods of the next word in the sequence. ("world!" might have a 75% chance of following "Hello," and "Nurse!" might have a 25% chance). It is straightforward to visit the chain and, following these probabilities, emit gibberish that mimics human writing.

For any given word, it is possible to "look behind" any number of words to determine how likely the word is to be the next in the sequence. The more words the text generator looks behind, the less random and more human-seeming will be the output.

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

$chainGenerator = new CliffordVickrey\Malarkey\Generator\ChainGenerator();
$markovChain = $chainGenerator->generateChain($text, 2);

// the chain stores probability data for every possible state, like so
var_dump($markovChain->getFrequenciesBySequence("I'll", 'buy')); // ['that' => 1, 'this' => 1]

$textGenerator = new CliffordVickrey\Malarkey\Generator\TextGenerator();
$output = $textGenerator->generateText($markovChain, ['maxSentences' => 1]);

var_dump($output); // e.g. I'll buy that for two dollars!
```

Command line text generation utilities are also available. For help, run this in your project folder:

```bash
$ php vendor/bin/malarkey
```

### TextGenerator

#### @generateChain
Generates a Markov chain from source text.

Returns: `ChainInterface`

Arguments:
* `text` (`string`): The source text
* `lookBehind` (`int`): The number of words to look behind when determining the next state of the Markov chain. The higher the number, the more coherent will be the randomly-generated text. Defaults to 2

The returned chain object implements `Serializable` and `JsonSerializable` for persistence and portability purposes.

```php
$text = "I'll buy that for a dollar! But I'll buy this for two dollars!";

$chainGenerator = new \CliffordVickrey\Malarkey\Generator\ChainGenerator();
/** @var CliffordVickrey\Malarkey\MarkovChain\Chain $markovChain */
$markovChain = $chainGenerator->generateChain($text);

$className = CliffordVickrey\Malarkey\MarkovChain\Chain::class;
$serialized = serialize($markovChain);
$unSerialized = unserialize($serialized, ['allowed_classes' => [$className]]);

var_dump(json_encode($markovChain) === json_encode($unSerialized)); // TRUE
```

#### @getLastGeneratedChunkCount
Returns the paragraph count of the text previously passed to `generateChain`, or NULL if no chain has been generated.

#### @getLastGeneratedWord
Returns the word count of the text previously passed to `generateChain`, or NULL if no chain has been generated.

### TextGenerator

#### @generateText
Visits a Markov chain and returns randomly generated text.

Returns: `string`

Arguments:
* `chain` (`ChainInterface`): The object representation of a Markov Chain
* `options` (`mixed`): Either an instance of `TextGeneratorOptionsInterface`, an associative array of options, or NULL for defaults.

Valid options (none are required):
* `chunkSeparator` (`string`): "Glue" to concatenate chunks emitted by the text generator. Defaults to two newlines
* `maxChunks` (`int`): Maximum number of chunks (paragraphs separated by line breaks) to generate. If no `maxChunks`, `maxSentences`, or `maxWords` provided, the generator will emit one chunk
* `maxSentences` (`int`): Maximum number of sentences to generate. Defaults to NULL
* `maxWords` (`int`): Maximum number of words to generator. Defaults to NULL
* `wordSeparator` (`string`): "Glue" to concatenate words emitted by the text generator. Defaults to " "
