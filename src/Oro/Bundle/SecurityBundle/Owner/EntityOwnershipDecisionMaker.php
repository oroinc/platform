<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\OwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;

class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker implements
    OwnershipDecisionMakerInterface
{
    /**
     * @deprecated since 1.8, use getTreeProvider method instead
     *
     * @var OwnerTreeProvider
     */
    protected $treeProvider;

    /**
     * @deprecated since 1.8, use getObjectIdAccessor method instead
     *
     * @var ObjectIdAccessor
     */
    protected $objectIdAccessor;

    /**
     * @deprecated since 1.8, use getEntityOwnerAccessor method instead
     *
     * @var EntityOwnerAccessor
     */
    protected $entityOwnerAccessor;

    /**
     * @deprecated since 1.8, use getMetadataProvider method instead
     *
     * @var OwnershipMetadataProvider
     */
    protected $metadataProvider;

    /**
     * Constructor
     *
     * @param OwnerTreeProvider         $treeProvider
     * @param ObjectIdAccessor          $objectIdAccessor
     * @param EntityOwnerAccessor       $entityOwnerAccessor
     * @param OwnershipMetadataProvider $metadataProvider
     *
     * @deprecated since 1.8,
     *      use getTreeProvider, getObjectIdAccessor, getEntityOwnerAccessor, getMetadataProvider method instead
     */
    public function __construct(
        OwnerTreeProvider $treeProvider,
        ObjectIdAccessor $objectIdAccessor,
        EntityOwnerAccessor $entityOwnerAccessor,
        OwnershipMetadataProvider $metadataProvider
    ) {
        $this->treeProvider = $treeProvider;
        $this->objectIdAccessor = $objectIdAccessor;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isGlobalLevelEntity() instead
     */
    public function isOrganization($domainObject)
    {
        return $this->isGlobalLevelEntity($domainObject);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isLocalLevelEntity() instead
     */
    public function isBusinessUnit($domainObject)
    {
        return $this->isLocalLevelEntity($domainObject);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isBasicLevelEntity() instead
     */
    public function isUser($domainObject)
    {
        return $this->isBasicLevelEntity($domainObject);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @deprecated since 1.8 Please use isAssociatedWithGlobalLevelEntity() instead
     */
    public function isAssociatedWithOrganization($user, $domainObject, $organization = null)
    {
        return $this->isAssociatedWithGlobalLevelEntity($user, $domainObject, $organization);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isAssociatedWithDeepLevelEntity() instead
     */
    public function isAssociatedWithBusinessUnit($user, $domainObject, $deep = false, $organization = null)
    {
        return $this->isAssociatedWithLocalLevelEntity($user, $domainObject, $deep, $organization);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isAssociatedWithBasicLevelEntity() instead
     */
    public function isAssociatedWithUser($user, $domainObject, $organization = null)
    {
        return $this->isAssociatedWithBasicLevelEntity($user, $domainObject, $organization);
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof User;
    }
}
