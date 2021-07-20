<?php

namespace Oro\Bundle\OrganizationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets an owner and an organization for an entity
 * based on the current security context these associations were not set yet.
 */
class SetOwnershipAssociations implements ProcessorInterface
{
    /** @var EntityOwnershipAssociationsSetter */
    private $entityOwnershipAssociationsSetter;

    public function __construct(EntityOwnershipAssociationsSetter $entityOwnershipAssociationsSetter)
    {
        $this->entityOwnershipAssociationsSetter = $entityOwnershipAssociationsSetter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $this->entityOwnershipAssociationsSetter->setOwnershipAssociations($context->getData());
    }
}
