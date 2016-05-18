<?php

namespace Oro\Bundle\FormBundle\Validator;

use Symfony\Component\Validator\Constraint;

class ConstraintFactory
{
    /**
     * Creates a validation constraint.
     *
     * @param string $name    The name of standard constraint or FQCN of a custom constraint
     * @param mixed  $options The constraint options (as associative array)
     *                        or the value for the default option (any other type)
     *
     * @return Constraint
     *
     * @throws \InvalidArgumentException if unknown constraint detected
     */
    public function create($name, $options = null)
    {
        if (strpos($name, '\\') !== false && class_exists($name)) {
            $className = (string)$name;
        } else {
            $className = 'Symfony\\Component\\Validator\\Constraints\\' . $name;
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" class does not exist.', $className)
            );
        }

        /**
         * Fixed issue with array pointer on php7
         */
        if (is_array($options)) {
            reset($options);
        }

        return new $className($options);
    }

    /**
     * Creates validation constraints by a given definition.
     *
     * @param array $constraints The definition of constraints
     *
     * @return Constraint[]
     *
     * @throws \InvalidArgumentException if invalid definition detected
     */
    public function parse(array $constraints)
    {
        $result = [];
        foreach ($constraints as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $name => $options) {
                    $result[] = $this->create($name, $options);
                }
            } elseif ($value instanceof Constraint) {
                $result[] = $value;
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected that each element in the constraints array must be either'
                        . ' instance of "Symfony\Component\Validator\Constraint"'
                        . ' or "array(constraint name => constraint options)".'
                        . ' Found "%s" element.',
                        is_object($value) ? get_class($value) : gettype($value)
                    )
                );
            }
        }

        return $result;
    }
}
