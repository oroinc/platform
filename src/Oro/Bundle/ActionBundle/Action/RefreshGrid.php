<?php

namespace Oro\Bundle\ActionBundle\Action;

use Oro\Bundle\ActionBundle\Exception\InvalidParameterException;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

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
