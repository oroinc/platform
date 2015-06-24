<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * This class implements AccessLevelOwnershipDecisionMakerInterface interface and allows to make ownership related
 * decisions using the tree of owners.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     *
     * @return $this
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
        return $this;
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
     */
    public function isGlobalLevelEntity($domainObject)
    {
        return is_a($domainObject, $this->metadataProvider->getOrganizationClass());
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
     */
    public function isLocalLevelEntity($domainObject)
    {
        return is_a($domainObject, $this->metadataProvider->getBusinessUnitClass());
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
     * @return bool
     */
    public function isBasicLevelEntity($domainObject)
    {
        return is_a($domainObject, $this->metadataProvider->getUserClass());
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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function isAssociatedWithGlobalLevelEntity($user, $domainObject, $organization = null)
    {
        $tree = $this->treeProvider->getTree();
        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        $organizationId = null;
        if ($organization) {
            $organizationId = $this->getOrganizationId($organization);
        }

        $userOrganizationIds = $tree->getUserOrganizationIds($this->getObjectId($user));
        if (empty($userOrganizationIds) || ($organizationId && !in_array($organizationId, $userOrganizationIds))) {
            return false;
        }

        $allowedOrganizationIds = $organizationId ? [$organizationId] : $userOrganizationIds;

        if ($this->isGlobalLevelEntity($domainObject)) {
            return in_array(
                $this->getObjectId($domainObject),
                $allowedOrganizationIds
            );
        }

        if ($this->isLocalLevelEntity($domainObject)) {
            return in_array(
                $tree->getBusinessUnitOrganizationId($this->getObjectId($domainObject)),
                $allowedOrganizationIds
            );
        }

        if ($this->isBasicLevelEntity($domainObject)) {
            $userId = $this->getObjectId($user);
            $objId = $this->getObjectId($domainObject);
            if ($userId === $objId) {
                $userOrganizationId = $tree->getUserOrganizationId($userId);
                $objOrganizationId = $tree->getUserOrganizationId($objId);

                return $userOrganizationId !== null && $userOrganizationId === $objOrganizationId;
            }
        }

        $metadata = $this->getObjectMetadata($domainObject);
        if (!$metadata->hasOwner()) {
            return false;
        }

        $ownerId = $this->getObjectIdIgnoreNull($this->getOwner($domainObject));
        if ($metadata->isGlobalLevelOwned()) {
            return $organizationId ? $ownerId === $organizationId : in_array($ownerId, $userOrganizationIds);
        } else {
            return in_array(
                $this->getObjectId($this->entityOwnerAccessor->getOrganization($domainObject)),
                $allowedOrganizationIds
            );
        }

        return false;
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
     */
    public function isAssociatedWithLocalLevelEntity($user, $domainObject, $deep = false, $organization = null)
    {
        $tree = $this->treeProvider->getTree();
        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        $organizationId = null;
        if ($organization) {
            $organizationId = $this->getObjectId($organization);
        }

        if ($this->isLocalLevelEntity($domainObject)) {
            return $this->isUserBusinessUnit(
                $this->getObjectId($user),
                $this->getObjectId($domainObject),
                $deep,
                $organizationId
            );
        }

        if ($this->isBasicLevelEntity($domainObject)) {
            $userId = $this->getObjectId($user);
            if ($userId === $this->getObjectId($domainObject) && $tree->getUserBusinessUnitId($userId) !== null) {
                return true;
            }
        }

        $metadata = $this->getObjectMetadata($domainObject);
        if (!$metadata->hasOwner()) {
            return false;
        }

        $ownerId = $this->getObjectIdIgnoreNull($this->getOwner($domainObject));
        if ($metadata->isLocalLevelOwned()) {
            return $this->isUserBusinessUnit($this->getObjectId($user), $ownerId, $deep, $organizationId);
        } elseif ($metadata->isBasicLevelOwned()) {
            $ownerBusinessUnitIds = $tree->getUserBusinessUnitIds($ownerId, $organizationId);
            if (empty($ownerBusinessUnitIds)) {
                return false;
            }

            return $this->isUserBusinessUnits(
                $this->getObjectId($user),
                $ownerBusinessUnitIds,
                $deep,
                $organizationId
            );
        }

        return false;
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
    public function isAssociatedWithBasicLevelEntity($user, $domainObject, $organization = null)
    {
        $userId = $this->getObjectId($user);
        if ($organization
            && !in_array(
                $this->getObjectId($organization),
                $this->treeProvider->getTree()->getUserOrganizationIds($userId)
            )
        ) {
            return false;
        }

        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        if ($this->isBasicLevelEntity($domainObject)) {
            return $this->getObjectId($domainObject) === $this->getObjectId($user);
        }

        $metadata = $this->getObjectMetadata($domainObject);
        if ($metadata->isBasicLevelOwned()) {
            $ownerId = $this->getObjectIdIgnoreNull($this->getOwner($domainObject));

            return $userId === $ownerId;
        }

        return false;
    }

    /**
     * Determines whether the given user has a relation to the given business unit
     *
     * @param  int|string      $userId
     * @param  int|string|null $ownerBusinessUnitIds
     * @param  bool            $deep Specify whether subordinate business units should be checked. Defaults to false.
     * @param  int|null        $organizationId
     * @return bool
     */
    protected function isUserBusinessUnits($userId, $ownerBusinessUnitIds, $deep = false, $organizationId = null)
    {
        $userBusinessUnitIds = $this->treeProvider->getTree()->getUserBusinessUnitIds($userId, $organizationId);
        $familiarBusinessUnits = array_intersect($userBusinessUnitIds, $ownerBusinessUnitIds);
        if (!empty($familiarBusinessUnits)) {
            return true;
        }
        if ($deep) {
            foreach ($userBusinessUnitIds as $buId) {
                $familiarBusinessUnits = array_intersect(
                    $this->treeProvider->getTree()->getSubordinateBusinessUnitIds($buId),
                    $ownerBusinessUnitIds
                );
                if (!empty($familiarBusinessUnits)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determines whether the given user has a relation to the given business unit
     *
     * @param  int|string      $userId
     * @param  int|string|null $businessUnitId
     * @param  bool            $deep Specify whether subordinate business units should be checked. Defaults to false.
     * @param  int|null        $organizationId
     * @return bool
     */
    protected function isUserBusinessUnit($userId, $businessUnitId, $deep = false, $organizationId = null)
    {
        if ($businessUnitId === null) {
            return false;
        }

        foreach ($this->treeProvider->getTree()->getUserBusinessUnitIds($userId, $organizationId) as $buId) {
            if ($businessUnitId === $buId) {
                return true;
            }
            if ($deep
                && in_array($businessUnitId, $this->treeProvider->getTree()->getSubordinateBusinessUnitIds($buId))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->securityFacade && $this->securityFacade->getLoggedUser() instanceof User;
    }
}
