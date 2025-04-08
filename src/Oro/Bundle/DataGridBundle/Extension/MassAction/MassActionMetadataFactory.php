<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;

/**
 * Creates metadata for the given mass action.
 */
class MassActionMetadataFactory
{
    public function __construct(private readonly ActionMetadataFactory $actionMetadataFactory)
    {
    }

    public function createActionMetadata(MassActionInterface $action): array
    {
        return $this->actionMetadataFactory->createActionMetadata($action);
    }
}
