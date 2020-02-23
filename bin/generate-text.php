<?php

/**
 * Simple console app for generating random text
 */

declare(strict_types=1);

use CliffordVickrey\Malarkey\Exception\TypeException;
use CliffordVickrey\Malarkey\Generator\ChainGenerator;
use CliffordVickrey\Malarkey\Generator\TextGenerator;

require_once __DIR__ . '/../vendor/autoload.php';

call_user_func(function (array $arguments): void {
    $returnCode = 0;
    $output = '';

    try {
        if ('cli' !== PHP_SAPI) {
            throw new RuntimeException('This script is command-line only');
        }

        if (0 === count($arguments)) {
            throw new RuntimeException('Failed to parse command line arguments');
        }

        if (1 === count($arguments)) {
            $help = [
                'Emits randomly-generated text from a source file using a Markov chain model',
                '',
                'Arguments:',
                '    -source [source]: The source filename containing text',
                '    -sentences [sentences]: The number of sentences to generate',
                '    -words [words]: Alternatively, the number of words to generate',
                '    -coherence [coherence]: Coherence of the random text. Defaults to "2"',
                '',
                'Switches:',
                '    --ignore-line-breaks: Ignore line breaks in the source text',
                '    --log-performance: Emit performance measures as well',
                ''
            ];

            $output = implode(PHP_EOL, $help);
        } else {
            $options = [
                'coherence' => 2,
                'ignore-line-breaks' => false,
                'log-performance' => false,
                'sentences' => null,
                'words' => null
            ];

            $filterArgument = function (string $name, string $value) {
                switch ($name) {
                    case 'coherence':
                    case 'sentences':
                    case 'words':
                        $filtered = filter_var($value, FILTER_SANITIZE_NUMBER_INT);

                        if (false === $filtered) {
                            throw new RuntimeException(sprintf(
                                'Invalid value provided for option "%s"; expected integer', $filtered
                            ));
                        }

                        $filtered = (int)$filtered;

                        if ($filtered < 0) {
                            throw new RuntimeException(sprintf(
                                'Invalid value provided for option "%s"; expected integer greater than -1', $filtered
                            ));
                        }

                        if ('level' === $name && $filtered < 1) {
                            throw new RuntimeException(sprintf(
                                'Invalid value provided for option "%s"; expected integer greater than 0', $filtered
                            ));
                        }

                        return $filtered;
                    case 'source':
                        $filtered = trim(str_replace(chr(0), '', $value));
                        if ('' === $value) {
                            throw new RuntimeException(sprintf(
                                'Invalid value provided for option "%s"; expected non-empty value', $filtered
                            ));
                        }
                        return $filtered;
                    default:
                        throw new RuntimeException(sprintf('Unexpected option, "%s"', $name));
                }
            };

            $validArgumentNames = ['coherence', 'sentences', 'source', 'words'];
            $validSwitchNames = ['ignore-line-breaks', 'log-performance'];

            /** @var string|null $argumentName */
            $argumentName = null;
            /** @var string[] $argumentValues */
            $argumentValues = [];

            array_shift($arguments);

            $k = count($arguments) - 1;

            // who needs Symfony to write a console app when you have Spaghetty? :-)
            foreach ($arguments as $i => $argument) {
                if (!is_string($argument)) {
                    throw TypeException::fromVariable('argument', 'string', $argument);
                }

                $isSwitch = (bool)preg_match('/^--/', $argument);
                $isArgument = !$isSwitch && preg_match('/^-/', $argument);

                if (($isArgument || $isSwitch) && null !== $argumentName && null !== $argumentValues) {
                    $options[$argumentName] = $filterArgument($argumentName, implode(' ', $argumentValues));
                    $argumentName = null;
                    $argumentValues = [];
                } elseif (($isArgument || $isSwitch) && null !== $argumentName && 0 === count($argumentValues)) {
                    throw new RuntimeException(sprintf('No value provided for option "%s"', $argumentName));
                } elseif (!($isArgument || $isSwitch) && null === $argumentName) {
                    throw new RuntimeException(sprintf('Unexpected argument, "%s"', $argument));
                }

                if ($isSwitch) {
                    $switchName = (string)preg_replace('/^--/', '', $argument);
                    if (!in_array($switchName, $validSwitchNames)) {
                        throw new RuntimeException(sprintf('Invalid switch, "%s"', $switchName));
                    }
                    $options[$switchName] = true;
                } elseif ($isArgument) {
                    $argumentName = (string)preg_replace('/^-/', '', $argument);
                    if (!in_array($argumentName, $validArgumentNames)) {
                        throw new RuntimeException(sprintf('Invalid argument, "%s"', $argumentName));
                    }
                } else {
                    $argumentValues[] = $argument;
                    if ($i === $k) {
                        $options[$argumentName] = $filterArgument($argumentName, implode(' ', $argumentValues));
                    }
                }
            }

            if (null !== $argumentName && 0 === count($argumentValues)) {
                throw new RuntimeException(sprintf('No value provided for option "%s"', $argumentName));
            }

            if (!isset($options['source'])) {
                throw new RuntimeException('Required argument "source" missing');
            }

            if (!isset($options['words']) && !isset($options['sentences'])) {
                throw new RuntimeException('One of "words" and "sentences" must be passed as arguments');
            }

            if (!is_file($options['source'])) {
                throw new RuntimeException(sprintf('File %s does not exist', $options['source']));
            }

            $text = file_get_contents($options['source']);
            if (false === $text) {
                throw new RuntimeException(sprintf('Could not open %s for reading', $options['source']));
            }

            $startTime = null;
            if ($options['log-performance']) {
                $startTime = microtime(true);
            }

            $chainGenerator = new ChainGenerator();
            $textGenerator = new TextGenerator();

            $chain = $chainGenerator->generateChain($text, $options['coherence'], $options['ignore-line-breaks']);

            if (null !== $startTime) {
                $endTime = microtime(true);
                $output .= sprintf('Markov chain generated in %g seconds%s', $endTime - $startTime, PHP_EOL);
                $startTime = microtime(true);
            }

            $break = PHP_EOL . PHP_EOL;
            $outputText = $textGenerator->generateText($chain, $options['sentences'], $options['words'], ' ', $break);

            if (null !== $startTime) {
                $endTime = microtime(true);
                $wordCount = str_word_count($outputText);

                $output .= sprintf('%d words generated in %g seconds%s', $wordCount, $endTime - $startTime, PHP_EOL);
                $output .= sprintf('Peak memory usage: %dKB%s%s', memory_get_peak_usage() / 1024, PHP_EOL, PHP_EOL);
            }

            $output .= $outputText;
        }
    } catch (Throwable $e) {
        $output = sprintf('Fatal error: %s', $e->getMessage());
        $returnCode = 1;
    }

    echo $output;
    exit($returnCode);
}, $argv ?? []);
