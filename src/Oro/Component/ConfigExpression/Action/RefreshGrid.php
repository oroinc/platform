<?php

namespace Oro\Component\ConfigExpression\Action;

use Oro\Bundle\ActionBundle\Exception\InvalidParameterException;

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
        $this->contextAccessor->setValue($context, 'refreshGrid', $this->gridNames);
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
