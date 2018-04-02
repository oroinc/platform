<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class Substring extends AbstractAction
{
    const OPTION_START_POSITION = 'startPos';
    const OPTION_LENGTH = 'length';
    const OPTION_STRING = 'string';
    const OPTION_ATTRIBUTE = 'attribute';
    const DEFAULT_STARTING_POSITION = 0;

    /**
     * @var array
     */
    private $options;

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $sourceString = (string)$this->getOptionValue($context, self::OPTION_STRING);
        $startPosition = $this->options[self::OPTION_START_POSITION];

        if (empty($this->options[self::OPTION_LENGTH])) {
            $result = mb_substr($sourceString, $startPosition);
        } else {
            $result = mb_substr($sourceString, $startPosition, $this->options[self::OPTION_LENGTH]);
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], $result);
    }

    /**
     * Allowed options:
     *  - attribute - contains property path used to save result string
     *  - string - string used to take substring of
     *  - startPos - starting position of substring's, 0 if not set
     *  - length - length of substring
     *
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_ATTRIBUTE])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (!$options[self::OPTION_ATTRIBUTE] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        if (empty($options[self::OPTION_STRING])) {
            throw new InvalidParameterException('String parameter must be specified');
        }

        if (empty($options[self::OPTION_START_POSITION])) {
            $options[self::OPTION_START_POSITION] = self::DEFAULT_STARTING_POSITION;
        }

        $this->ensureIsIntegerOption($options, self::OPTION_START_POSITION);
        $this->ensureIsIntegerOption($options, self::OPTION_LENGTH);

        $this->options = $options;

        return $this;
    }

    /**
     * @param array $options
     * @param string $optionName
     * @throws InvalidParameterException
     */
    private function ensureIsIntegerOption(array $options, $optionName)
    {
        if (array_key_exists($optionName, $options) && !is_int($options[$optionName])) {
            throw new InvalidParameterException(sprintf('%s option must be integer', $optionName));
        }
    }

    /**
     * @param mixed $context
     * @param string $optionName
     * @return string
     */
    private function getOptionValue($context, $optionName)
    {
        return $this->contextAccessor->getValue($context, $this->options[$optionName]);
    }
}
