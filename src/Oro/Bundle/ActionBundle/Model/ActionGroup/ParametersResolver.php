<?php

namespace Oro\Bundle\ActionBundle\Model\ActionGroup;

use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\Common\Collections\Collection;

use Oro\Component\Action\Exception\InvalidParameterException;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\Parameter;

class ParametersResolver
{
    /** @var array */
    private static $typeAliases = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
    ];

    /**
     * @param ActionData $data
     * @param ActionGroup $actionGroup
     * @param Collection|null $errors
     * @throws InvalidParameterException
     */
    public function resolve(ActionData $data, ActionGroup $actionGroup, Collection $errors = null)
    {
        $violations = [];

        foreach ($actionGroup->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            if ($data->offsetExists($parameterName)) {
                if ($parameter->hasTypeHint()) {
                    $valid = $this->isValidType(
                        $data->offsetGet($parameterName),
                        $parameter->getType(),
                        $message
                    );
                    if (false === $valid) {
                        $this->addViolation(
                            $violations,
                            $parameter,
                            $message,
                            $data->offsetGet($parameterName)
                        );
                    }
                }
            } else {
                if ($parameter->isRequired()) {
                    $this->addViolation($violations, $parameter, 'parameter is required');
                } else {
                    $data->offsetSet($parameterName, $parameter->getDefault());
                }
            }
        }

        if (0 !== count($violations)) {
            if (null !== $errors) {
                $this->delegateErrors($violations, $errors);
            }

            throw new InvalidParameterException(
                sprintf(
                    'Trying to execute ActionGroup "%s" with invalid or missing parameter(s): "%s"',
                    $actionGroup->getDefinition()->getName(),
                    implode('", "', array_keys($violations))
                )
            );
        }
    }

    /**
     * @param string $value
     * @param string $type
     * @param string $message
     * @return string|true Error message or null
     */
    private function isValidType($value, $type, &$message)
    {
        $type = array_key_exists($type, self::$typeAliases) ? self::$typeAliases[$type] : $type;

        if ((function_exists($isFunction = 'is_' . $type) && $isFunction($value)) ||
            ($value instanceof $type)
        ) {
                return true;
        }

        $message = sprintf(
            'Value %s is expected to be of type "%s", but is of type "%s".',
            $this->formatValue($value),
            $type,
            is_object($value) ? get_class($value) : gettype($value)
        );

        return false;
    }

    /**
     * @param array $violations
     * @param Collection $errors
     */
    private function delegateErrors(array &$violations, Collection $errors)
    {
        foreach ($violations as $errorBody) {
            $errors->add($errorBody);
        }
    }

    /**
     * @param array $violations reference to violations array that will be modified with new one
     * @param Parameter $parameter
     * @param string $reason message of violation
     * @param mixed $value
     */
    private function addViolation(array &$violations, Parameter $parameter, $reason, $value = null)
    {
        $parameterName = $parameter->getName();

        $message = 'Parameter `{{ parameter }}` validation failure. Reason: {{ reason }}.';

        if ($parameter->hasMessage()) {
            $message = $parameter->getMessage();
        }

        $violations[$parameterName] = [
            'message' => $message,
            'parameters' => [
                '{{ reason }}' => $reason,
                '{{ parameter }}' => $parameterName,
                '{{ type }}' => $parameter->getType(),
                '{{ value }}' => $this->formatValue($value)
            ]
        ];
    }

    /**
     * Returns a string representation of the value.
     *
     * This method returns the equivalent PHP tokens for most scalar types
     * (i.e. "false" for false, "1" for 1 etc.). Strings are always wrapped
     * in double quotes (").
     *
     * @param mixed $value The value to format as string
     *
     * @return string The string representation of the passed value
     */
    private function formatValue($value)
    {
        if (is_object($value)) {
            return get_class($value);
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if (is_resource($value)) {
            return 'resource';
        }

        if (null === $value) {
            return 'null';
        }

        if (false === $value) {
            return 'false';
        }

        if (true === $value) {
            return 'true';
        }

        return (string)$value;
    }
}
