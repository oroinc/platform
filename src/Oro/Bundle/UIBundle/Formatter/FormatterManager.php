<?php

namespace Oro\Bundle\UIBundle\Formatter;

use Oro\Bundle\UIBundle\Exception\InvalidFormatterException;
use Psr\Container\ContainerInterface;

/**
 * The manager that delegates formatting of a value to child formatters
 * and allows to guess formatters by data type.
 */
class FormatterManager
{
    /** @var ContainerInterface */
    private $formatters;

    /** @var array [data type => formatter name, ...] */
    private $typesMap;

    /**
     * @param ContainerInterface $formatters
     * @param array              $typesMap [data type => formatter name, ...]
     */
    public function __construct(ContainerInterface $formatters, array $typesMap)
    {
        $this->formatters = $formatters;
        $this->typesMap = $typesMap;
    }

    /**
     * Applies the given formatter to the given parameter.
     *
     * @param mixed  $parameter
     * @param string $formatterName
     * @param array  $formatterArguments
     *
     * @return mixed
     *
     * @throws InvalidFormatterException if the requested formatter does not exist
     */
    public function format($parameter, string $formatterName, array $formatterArguments = [])
    {
        if (!$this->formatters->has($formatterName)) {
            throw new InvalidFormatterException(sprintf('The formatter "%s" does not exist.', $formatterName));
        }

        /** @var FormatterInterface $formatter */
        $formatter = $this->formatters->get($formatterName);

        if (null === $parameter) {
            return $formatter->getDefaultValue();
        }

        return $formatter->format($parameter, $formatterArguments);
    }

    /**
     * Guesses formatter for given data type.
     *
     * @param string $type The data type
     *
     * @return string|null The formatter name or NULL if there is no suitable formatter
     */
    public function guessFormatter(string $type): ?string
    {
        return $this->typesMap[$type] ?? null;
    }
}
