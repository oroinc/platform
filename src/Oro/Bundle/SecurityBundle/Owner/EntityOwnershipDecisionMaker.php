<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(
        OwnerTreeProviderInterface $treeProvider,
        ObjectIdAccessor $objectIdAccessor,
        EntityOwnerAccessor $entityOwnerAccessor,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        TokenAccessorInterface $tokenAccessor
    ) {
        parent::__construct($treeProvider, $objectIdAccessor, $entityOwnerAccessor, $ownershipMetadataProvider);
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->tokenAccessor->getUser() instanceof User;
    }
}
