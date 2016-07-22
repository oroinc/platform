<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\InvalidParameterException;

class Count extends AbstractAction
{
    /** @var array */
    protected $options;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!isset($options['array'])) {
            throw new InvalidParameterException('Array parameter is required.');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required.');
        }
        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $array = $this->contextAccessor->getValue($context, $this->options['array']);
        if ($array instanceof Collection) {
            $array = $array->toArray();
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], count($array));
    }
}
