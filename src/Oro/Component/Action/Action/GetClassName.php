<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Retrieves the class name of an object and stores it in a context attribute.
 *
 * This action extracts the fully qualified class name from an object using Doctrine's {@see ClassUtils},
 * which properly handles proxy objects. If the value is not an object, `null` is stored instead.
 * Useful for dynamic type checking and conditional logic based on object types.
 */
class GetClassName extends AbstractAction
{
    /**
     * @var array
     */
    protected $options;

    #[\Override]
    protected function executeAction($context)
    {
        $object = $this->contextAccessor->getValue($context, $this->options['object']);
        $class = is_object($object) ? ClassUtils::getClass($object) : null;
        $this->contextAccessor->setValue($context, $this->options['attribute'], $class);
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options['object'])) {
            throw new InvalidParameterException('Object parameter is required');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        $this->options = $options;

        return $this;
    }
}
