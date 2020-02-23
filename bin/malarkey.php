<?php

/**
 * Simple console app for generating random text
 */

declare(strict_types=1);

use CliffordVickrey\Malarkey\Exception\TypeException;
use CliffordVickrey\Malarkey\Generator\ChainGenerator;
use CliffordVickrey\Malarkey\Generator\TextGenerator;

call_user_func(function (array $arguments): void {
    foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }

    $arguments = array_values($arguments);
    $returnCode = 0;
    $output = '';

    try {
        if (0 === count($arguments)) {
            throw new RuntimeException('Failed to parse command line arguments');
        }

        $command = $arguments[1] ?? null;

        if (1 === count($arguments) || 'help' === $command) {
            $help = [
                'Markov chain command-line utilities',
                'Usage: markov.php [generate-text|generate-chain]',
                '',
                'generate-text',
                'Emits randomly-generated text using a Markov chain algorithm.',
                '    -source [source]: The source filename containing text. Use multiple -source arguments to '
                . 'concatenate files',
                '    -sentences [sentences]: The number of sentences to generate',
                '    -words [words]: Alternatively, the number of words to generate',
                '    -lookback [lookback]: Number of words in the chain to "look back" when resolving the next word',
                '    --ignore-line-breaks: Ignore line breaks in the source text',
                '    --log-performance: Emit performance measures as well',
                '    --unserialize: Treat the source file as a serialized Markov chain instead of raw text',
                '',
                'generate-chain',
                'Emits a serialized Markov chain object.',
                '    -source [source]: The source filename containing text. Use multiple -source arguments to '
                . 'concatenate files',
                '    -lookback [lookback]: Number of words in the chain to "look back" when resolving the next word',
                '    --ignore-line-breaks: Ignore line breaks in the source text',
                ''
            ];

            $output = implode(PHP_EOL, $help);
        } else {
            $validCommands = ['generate-text', 'generate-chain'];

            if (!in_array($command, $validCommands)) {
                throw new RuntimeException(sprintf('Invalid command, "%s"', $command));
            }

            $filterArgument = function (string $name, string $value, $initialValue) {
                switch ($name) {
                    case 'lookback':
                    case 'sentences':
                    case 'words':
                        if (null !== $initialValue) {
                            throw new RuntimeException(sprintf('Duplicate arguments for "%s"', $name));
                        }

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

                        if (is_array($initialValue)) {
                            return array_merge($initialValue, [$filtered]);
                        }

                        return [$filtered];
                    default:
                        throw new RuntimeException(sprintf('Unexpected option, "%s"', $name));
                }
            };

            $generateText = 'generate-text' === $command;

            $options = [
                'ignore-line-breaks' => false,
                'log-performance' => false,
                'lookback' => null,
                'sentences' => null,
                'source' => null,
                'unserialize' => null,
                'words' => null
            ];

            if ($generateText) {
                $validArgumentNames = ['lookback', 'sentences', 'source', 'words'];
                $validSwitchNames = ['ignore-line-breaks', 'log-performance', 'unserialize'];
            } else {
                $validArgumentNames = ['lookback', 'source'];
                $validSwitchNames = ['ignore-line-breaks'];
            }

            /** @var string|null $argumentName */
            $argumentName = null;
            /** @var string[] $argumentValues */
            $argumentValues = [];

            $arguments = array_slice($arguments, 2);

            $k = count($arguments) - 1;

            // who needs Symfony to write a console app when you have Spaghetty? :-)
            foreach ($arguments as $i => $argument) {
                if (!is_string($argument)) {
                    throw TypeException::fromVariable('argument', 'string', $argument);
                }

                $isSwitch = (bool)preg_match('/^--/', $argument);
                $isArgument = !$isSwitch && preg_match('/^-/', $argument);

                if (($isArgument || $isSwitch) && null !== $argumentName && null !== $argumentValues) {
                    $options[$argumentName] = $filterArgument(
                        $argumentName,
                        implode(' ', $argumentValues),
                        $options[$argumentName]
                    );
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

                    if ($options[$switchName]) {
                        throw new RuntimeException(sprintf('Duplicate switch, "%s"', $switchName));
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
                        $options[$argumentName] = $filterArgument(
                            $argumentName,
                            implode(' ', $argumentValues),
                            $options[$argumentName]
                        );
                    }
                }
            }

            if (null !== $argumentName && 0 === count($argumentValues)) {
                throw new RuntimeException(sprintf('No value provided for option "%s"', $argumentName));
            }

            if (!isset($options['source'])) {
                throw new RuntimeException('Required argument "source" missing');
            }

            if ($generateText) {
                if (!isset($options['words']) && !isset($options['sentences'])) {
                    throw new RuntimeException('One of "words" and "sentences" must be passed as arguments');
                }

                if (!isset($options['lookback'])) {
                    $options['lookback'] = 2;
                }
            }

            $text = '';
            foreach ($options['source'] as $i => $fileName) {
                if (!is_file($fileName)) {
                    throw new RuntimeException(sprintf('File %s does not exist', $fileName));
                }


                $fileContents = file_get_contents($fileName);
                if (false === $fileContents) {
                    throw new RuntimeException(sprintf('Could not open %s for reading', $fileName));
                }
                $text .= (($i && $generateText) ? "\n" : '') . $fileContents;
            }

            $startTime = null;
            if ($options['log-performance']) {
                $startTime = microtime(true);
            }

            if (isset($options['unserialize']) && $options['unserialize']) {
                $action = 'unserialized';
                $chain = unserialize($text);
            } else {
                $action = 'generated';
                $chainGenerator = new ChainGenerator();
                $chain = $chainGenerator->generateChain($text, $options['lookback'], $options['ignore-line-breaks']);
            }

            if (null !== $startTime) {
                $elapsed = microtime(true) - $startTime;
                $output .= sprintf('Markov chain %s in %g seconds%s', $action, $elapsed, PHP_EOL);
                $startTime = microtime(true);
            }

            if ($generateText) {
                $break = PHP_EOL . PHP_EOL;
                $textGenerator = new TextGenerator();
                $outputText = $textGenerator->generateText($chain, $options['sentences'], $options['words'], ' ', $break);

                if (null !== $startTime) {
                    $elapsed = microtime(true) - $startTime;
                    $peakMemoryUsage = memory_get_peak_usage() / 1024;
                    $wordCount = str_word_count($outputText);

                    $output .= sprintf('%d words generated in %g seconds%s', $wordCount, $elapsed, PHP_EOL);
                    $output .= sprintf('Peak memory usage: %dKB%s%s', $peakMemoryUsage, PHP_EOL, PHP_EOL);
                }

                $output .= $outputText;
            } else {
                $output = serialize($chain);
            }

        }
    } catch (Throwable $e) {
        $output = sprintf('Fatal error: %s', $e->getMessage());
        $returnCode = 1;
    }

    echo $output;
    exit($returnCode);
}, $argv ?? []);
