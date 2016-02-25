<?php

namespace Oro\Component\ConfigExpression\Action;

use Oro\Component\ConfigExpression\Exception\InvalidParameterException;

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
        $gridNames = [];

        foreach ($this->gridNames as $gridName) {
            $gridNames[] = $this->contextAccessor->getValue($context, $gridName);
        }

        $this->contextAccessor->setValue($context, 'refreshGrid', $gridNames);
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (empty($options)) {
            throw new InvalidParameterException('Gridname parameter must be specified');
        }

        $this->gridNames = array_unique($options);

        return $this;
    }
}
