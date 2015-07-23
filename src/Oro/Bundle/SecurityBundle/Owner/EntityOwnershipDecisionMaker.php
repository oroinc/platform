<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Acl\Extension\OwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Model\AceAwareModelInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\UserBundle\Entity\User;

class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker implements
    OwnershipDecisionMakerInterface, AceAwareModelInterface
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
     * @var ConfigProvider
     */
    protected $configProvider;

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
     * @param ConfigProvider            $configProvider
     */
    public function __construct(
        OwnerTreeProvider $treeProvider,
        ObjectIdAccessor $objectIdAccessor,
        EntityOwnerAccessor $entityOwnerAccessor,
        OwnershipMetadataProvider $metadataProvider,
        ConfigProvider $configProvider
    ) {
        $this->treeProvider = $treeProvider;
        $this->objectIdAccessor = $objectIdAccessor;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->metadataProvider = $metadataProvider;
        $this->configProvider = $configProvider;
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
     * @param object $organization
     * @param object $domainObject
     * @return bool
     * @throws \Exception
     */
    public function isSharedWithUser($user, $domainObject, $organization)
    {
        if (!$this->isSharingApplicable($domainObject)) {
            return false;
        }
        $organizationId = null;
        if ($organization) {
            $organizationId = $this->getObjectId($organization);
        }
        $tree = $this->treeProvider->getTree();
        $securityIdentity = $this->ace->getSecurityIdentity();
        // skip RoleSecurityIdentity because there is no way to share object for whole role
        if ($securityIdentity instanceof UserSecurityIdentity && $user instanceof UserInterface) {
            return $securityIdentity->equals(UserSecurityIdentity::fromAccount($user));
        } elseif ($securityIdentity instanceof BusinessUnitSecurityIdentity) {
            $ownerBusinessUnitIds = $tree->getUserBusinessUnitIds($this->getObjectId($user), $organizationId);
            $businessUnitClass = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
            foreach ($ownerBusinessUnitIds as $buId) {
                if ($securityIdentity->equals(new BusinessUnitSecurityIdentity($buId, $businessUnitClass))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param object $domainObject
     * @return bool
     */
    protected function isSharingApplicable($domainObject)
    {
        $entityName = ClassUtils::getClass($domainObject);
        $shareScopes = $this->configProvider->hasConfig($entityName)
            ? $this->configProvider->getConfig($entityName)->get('share_scopes')
            : null;
        if (!$this->ace || !$shareScopes) {
            return false;
        }

        $sharedToScope = false;
        if ($this->ace->getSecurityIdentity() instanceof UserSecurityIdentity) {
            $sharedToScope = 'user';
        } elseif ($this->ace->getSecurityIdentity() instanceof BusinessUnitSecurityIdentity) {
            $sharedToScope = 'business_unit';
        }

        return in_array($sharedToScope, $shareScopes);
    }
}
