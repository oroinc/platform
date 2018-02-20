<?php

namespace Oro\Bundle\UIBundle\Formatter;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\UIBundle\Exception\InvalidFormatterException;

class FormatterManager
{
    /**
     * @var array|FormatterInterface[]
     *   - key: formatter name
     *   - value: FormatterInterface formatter service
     */
    protected $formatters;

    /**
     * @param string             $formatterName
     * @param FormatterInterface $formatterProvider
     */
    public function addFormatter($formatterName, FormatterInterface $formatterProvider)
    {
        $this->formatters[$formatterName] = $formatterProvider;
    }

    /**
     * Apply formatter to the parameter
     *
     * @param mixed  $parameter
     * @param string $formatterName
     * @param array  $formatterArguments
     *
     * @return mixed
     * @throws InvalidFormatterException
     */
    public function format($parameter, $formatterName, array $formatterArguments = [])
    {
        if (!array_key_exists($formatterName, $this->formatters)) {
            throw new InvalidFormatterException(
                sprintf(
                    'Formatter %s not found',
                    $formatterName
                )
            );
        }
        $formatter = $this->formatters[$formatterName];

        if (null !== $parameter) {
            return $formatter->format($parameter, $formatterArguments);
        } else {
            return $formatter->getDefaultValue();
        }
    }

    /**
     * Guess formatters for given fieldConfigId
     * Returns array with data:
     *   - formatters: array with supported formatters for given field
     *   - default_formatter: default formatter for given field
     *
     * @param FieldConfigId $configId
     *
     * @return array|null
     */
    public function guessFormatters(FieldConfigId $configId)
    {
        $fieldType        = $configId->getFieldType();
        $formatters       = [];
        $defaultFormatter = null;
        $found            = false;

        foreach ($this->formatters as $formatterName => $formatter) {
            $isSupport = in_array($fieldType, $formatter->getSupportedTypes());
            if ($isSupport) {
                $found        = true;
                $formatters[] = $formatterName;
                if ($formatter->isDefaultFormatter()) {
                    $defaultFormatter = $formatterName;
                }
            }
        }

        if ($found) {
            return [
                'formatters'        => $formatters,
                'default_formatter' => $defaultFormatter
            ];
        }

        return null;
    }
}
