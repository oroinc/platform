<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;

class MassActionMetadataFactory
{
    /** @var ActionMetadataFactory */
    private $actionMetadataFactory;

    /**
     * @param ActionMetadataFactory $actionMetadataFactory
     */
    public function __construct(ActionMetadataFactory $actionMetadataFactory)
    {
        $this->actionMetadataFactory = $actionMetadataFactory;
    }

    /**
     * Creates metadata for the given action.
     *
     * @param MassActionInterface $action
     *
     * @return array
     */
    public function createActionMetadata(MassActionInterface $action)
    {
        return $this->actionMetadataFactory->createActionMetadata($action);
    }
}
