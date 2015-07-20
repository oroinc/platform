<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Acl\Extension\OwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AceAwareAclExtensionInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;

class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker implements
    OwnershipDecisionMakerInterface, AceAwareAclExtensionInterface
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
     * @var EntryInterface
     */
    protected $ace;

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
        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        if ($this->isSharedWithUser($user, $domainObject, $organization)) {
            return true;
        }
        
        return $this->isAssociatedWithGlobalLevelEntity($user, $domainObject, $organization);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isAssociatedWithDeepLevelEntity() instead
     */
    public function isAssociatedWithBusinessUnit($user, $domainObject, $deep = false, $organization = null)
    {
        $tree = $this->treeProvider->getTree();
        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        if ($this->isSharedWithUser($user, $domainObject, $organization)) {
            return true;
        }
        
        return $this->isAssociatedWithLocalLevelEntity($user, $domainObject, $deep, $organization);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isAssociatedWithBasicLevelEntity() instead
     */
    public function isAssociatedWithUser($user, $domainObject, $organization = null)
    {
        $userId = $this->getObjectId($user);
        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        if ($this->isSharedWithUser($user, $domainObject, $organization)) {
            return true;
        }
        
        return $this->isAssociatedWithBasicLevelEntity($user, $domainObject, $organization);
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    public function setAce(EntryInterface $ace)
    {
        $this->ace = $ace;
    }

    /**
     * @param object $user
     * @param object $domainObject
     * @param object $organization
     * @return bool
     * @throws \Exception
     */
    public function isSharedWithUser($user, $domainObject, $organization)
    {
        $organizationId = null;
        if ($organization) {
            $organizationId = $this->getObjectId($organization);
        }
        // todo rewrite method in pro
        $tree = $this->treeProvider->getTree();
        $securityIdentity = $this->ace->getSecurityIdentity();
        // skip RoleSecurityIdentity because there is no way to share object for whole role
        if ($securityIdentity instanceof UserSecurityIdentity) {
            return $securityIdentity->equals(UserSecurityIdentity::fromAccount($securityIdentity));
        } elseif ($securityIdentity instanceof BusinessUnitSecurityIdentity) {
            $ownerId = $this->getObjectIdIgnoreNull($this->getOwner($domainObject));
            $ownerBusinessUnitIds = $tree->getUserBusinessUnitIds($ownerId, $organizationId);
//            foreach ($user->getBusinessUnit() as $businessUnit) {
//                if ($securityIdentity->equals(BusinessUnitSecurityIdentity::fromBusinessUnit()))
//                return true;
//            }
        }

        return false;
    }
}
