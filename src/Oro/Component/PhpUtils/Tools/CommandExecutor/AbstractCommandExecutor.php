<?php

namespace Oro\Component\PhpUtils\Tools\CommandExecutor;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Contains common methods for preparing arguments before passing them to the Process instance
 */
abstract class AbstractCommandExecutor
{
    /** @var string|null */
    protected $env;

    /** @var array */
    protected $defaultOptions;

    protected function prepareParameters(string $command, array $params): array
    {
        $params = array_merge(
            [
                'command' => $command
            ],
            $params
        );

        if ($this->env && $this->env !== 'dev') {
            $params['--env'] = $this->env;
        }

        foreach ($this->defaultOptions as $name => $value) {
            $paramName = '--' . $name;
            if (!array_key_exists($paramName, $params)) {
                $params[$paramName] = $value;
            }
        }

        return $params;
    }

    /**
     * Sets arguments to correct format before passed to the Process instance
     *
     * @param array $processArguments
     * @param string $name
     * @param array|string|null $value
     */
    protected function processParameter(array &$processArguments, $name, $value)
    {
        if ($name && '-' === $name[0]) {
            if ($value === true) {
                $this->addParameter($processArguments, $name);
            } elseif ($value !== false) {
                $this->addParameter($processArguments, $name, $value);
            }
        } else {
            $this->addParameter($processArguments, $value);
        }
    }

    /**
     * @param array $processArguments
     * @param string $name
     * @param array|string|null $value
     */
    protected function addParameter(array &$processArguments, $name, $value = null)
    {
        if (null !== $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $processArguments[] = sprintf('%s=%s', $name, $item);
                }
            } else {
                $processArguments[] = sprintf('%s=%s', $name, $value);
            }
        } else {
            $processArguments[] = $name;
        }
    }

    /**
     * Finds the PHP executable.
     *
     * @return string
     * @throws FileNotFoundException
     */
    public static function getPhpExecutable()
    {
        $phpFinder = new PhpExecutableFinder();
        $phpPath   = $phpFinder->find();
        if (!$phpPath) {
            throw new FileNotFoundException('The PHP executable could not be found.');
        }

        return $phpPath;
    }
}
