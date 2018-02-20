<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class GetClassName extends AbstractAction
{
    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $object = $this->contextAccessor->getValue($context, $this->options['object']);
        $class = is_object($object) ? ClassUtils::getClass($object) : null;
        $this->contextAccessor->setValue($context, $this->options['attribute'], $class);
    }

    /**
     * {@inheritdoc}
     */
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
