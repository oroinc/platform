<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\InvalidParameterException;

class RefreshGrid extends AbstractAction
{
    /**
     * @var array
     */
    protected $gridNames;

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $property = new PropertyPath('refreshGrid');

        $gridNames = $this->contextAccessor->getValue($context, $property);
        $gridNames = array_map(
            function ($gridName) use ($context) {
                return $this->contextAccessor->getValue($context, $gridName);
            },
            array_merge((array)$gridNames, $this->gridNames)
        );

        $this->contextAccessor->setValue($context, $property, array_unique($gridNames));
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (empty($options)) {
            throw new InvalidParameterException('Gridname parameter must be specified');
        }

        $this->gridNames = $options;

        return $this;
    }
}
