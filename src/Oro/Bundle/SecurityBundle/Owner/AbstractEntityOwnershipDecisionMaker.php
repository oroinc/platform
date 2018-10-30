<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * This class implements AccessLevelOwnershipDecisionMakerInterface interface and allows to make ownership related
 * decisions using the tree of owners.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractEntityOwnershipDecisionMaker implements AccessLevelOwnershipDecisionMakerInterface
{
    /** @var OwnerTreeProviderInterface */
    protected $treeProvider;

    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /**
     * @param OwnerTreeProviderInterface         $treeProvider
     * @param ObjectIdAccessor                   $objectIdAccessor
     * @param EntityOwnerAccessor                $entityOwnerAccessor
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     */
    public function __construct(
        OwnerTreeProviderInterface $treeProvider,
        ObjectIdAccessor $objectIdAccessor,
        EntityOwnerAccessor $entityOwnerAccessor,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->treeProvider = $treeProvider;
        $this->objectIdAccessor = $objectIdAccessor;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isOrganization($domainObject)
    {
        return is_a(
            $domainObject instanceof DomainObjectReference ? $domainObject->getType() : $domainObject,
            $this->ownershipMetadataProvider->getOrganizationClass(),
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isBusinessUnit($domainObject)
    {
        return is_a(
            $domainObject instanceof DomainObjectReference ? $domainObject->getType() : $domainObject,
            $this->ownershipMetadataProvider->getBusinessUnitClass(),
            true
        );
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function isUser($domainObject)
    {
        return is_a(
            $domainObject instanceof DomainObjectReference ? $domainObject->getType() : $domainObject,
            $this->ownershipMetadataProvider->getUserClass(),
            true
        );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isAssociatedWithOrganization($user, $domainObject, $organization = null)
    {
        $tree = $this->treeProvider->getTree();
        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        $organizationId = null;
        if ($organization) {
            $organizationId = $this->getOrganizationId($organization);
        }

        $userOrganizationIds = $tree->getUserOrganizationIds($this->getObjectId($user));
        if (empty($userOrganizationIds)
            || ($organizationId && !in_array($organizationId, $userOrganizationIds, true))
        ) {
            return false;
        }

        $allowedOrganizationIds = $this->getAllowedOrganizationIds($user, $domainObject, $organization);

        if ($this->isOrganization($domainObject)) {
            return in_array(
                $this->getObjectId($domainObject),
                $allowedOrganizationIds,
                true
            );
        }

        if ($this->isBusinessUnit($domainObject)) {
            return in_array(
                $tree->getBusinessUnitOrganizationId($this->getObjectId($domainObject)),
                $allowedOrganizationIds
            );
        }

        if ($this->isUser($domainObject)) {
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
        if ($metadata->isOrganizationOwned()) {
            return $organizationId ? $ownerId === $organizationId : in_array($ownerId, $userOrganizationIds, true);
        }

        $ownerOrganization = $this->entityOwnerAccessor->getOrganization($domainObject);

        // in case when entity has no owner yet (e.g. checking for new object)
        $noOwnerExistsYet = is_null($ownerOrganization);

        return
            $noOwnerExistsYet
            || in_array($this->getObjectId($ownerOrganization), $allowedOrganizationIds, true);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isAssociatedWithBusinessUnit($user, $domainObject, $deep = false, $organization = null)
    {
        $tree = $this->treeProvider->getTree();
        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        $organizationId = null;
        if ($organization) {
            $organizationId = $this->getObjectId($organization);
        }

        if ($this->isBusinessUnit($domainObject)) {
            return $this->isUserBusinessUnit(
                $this->getObjectId($user),
                $this->getObjectId($domainObject),
                $deep,
                $organizationId
            );
        }

        if ($this->isUser($domainObject)) {
            $userId = $this->getObjectId($user);
            if ($userId === $this->getObjectId($domainObject) && $tree->getUserBusinessUnitId($userId) !== null) {
                return true;
            }
        }

        $metadata = $this->getObjectMetadata($domainObject);
        if (!$metadata->hasOwner()) {
            return false;
        }

        $ownerId = $domainObject instanceof DomainObjectReference ?
            $domainObject->getOwnerId() :
            $this->getObjectIdIgnoreNull($this->getOwner($domainObject));
        if ($metadata->isBusinessUnitOwned()) {
            return $this->isUserBusinessUnit($this->getObjectId($user), $ownerId, $deep, $organizationId);
        } elseif ($metadata->isUserOwned()) {
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
     */
    public function isAssociatedWithUser($user, $domainObject, $organization = null)
    {
        $userId = $this->getObjectId($user);
        if ($organization
            && !in_array(
                $this->getObjectId($organization),
                $this->treeProvider->getTree()->getUserOrganizationIds($userId),
                true
            )
        ) {
            return false;
        }

        $this->validateUserObject($user);
        $this->validateObject($domainObject);

        if ($this->isUser($domainObject)) {
            return $this->getObjectId($domainObject) === $this->getObjectId($user);
        }

        $metadata = $this->getObjectMetadata($domainObject);
        if ($metadata->isUserOwned()) {
            $ownerId = $domainObject instanceof DomainObjectReference
                ? $domainObject->getOwnerId()
                : $this->getObjectIdIgnoreNull($this->getOwner($domainObject));

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
     *
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
     *
     * @return bool
     */
    protected function isUserBusinessUnit($userId, $businessUnitId, $deep = false, $organizationId = null)
    {
        if ($businessUnitId === null) {
            return false;
        }

        $businessUnits = $this->treeProvider->getTree()->getUserBusinessUnitIds($userId, $organizationId);
        foreach ($businessUnits as $buId) {
            if ($businessUnitId === $buId) {
                return true;
            }
            if ($deep
                && in_array(
                    $businessUnitId,
                    $this->treeProvider->getTree()->getSubordinateBusinessUnitIds($buId),
                    true
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check that the given object is a user
     *
     * @param  object $user
     *
     * @throws InvalidDomainObjectException
     */
    protected function validateUserObject($user)
    {
        if (!is_object($user) || !$this->isUser($user)) {
            throw new InvalidDomainObjectException(
                sprintf(
                    '$user must be an instance of %s.',
                    $this->ownershipMetadataProvider->getUserClass()
                )
            );
        }
    }

    /**
     * Check that the given object is a domain object
     *
     * @param  object $domainObject
     *
     * @throws InvalidDomainObjectException
     */
    protected function validateObject($domainObject)
    {
        if (!is_object($domainObject)) {
            throw new InvalidDomainObjectException('$domainObject must be an object.');
        }
    }

    /**
     * Gets id for the given domain object
     *
     * @param  object $domainObject
     *
     * @return int|string
     * @throws InvalidDomainObjectException
     */
    protected function getObjectId($domainObject)
    {
        return $domainObject instanceof DomainObjectReference
            ? $domainObject->getIdentifier()
            : $this->objectIdAccessor->getId($domainObject);
    }

    /**
     * Gets id for the given domain object.
     * Returns null when the given domain object is null
     *
     * @param  object|null $domainObject
     *
     * @return int|string|null
     * @throws InvalidDomainObjectException
     */
    protected function getObjectIdIgnoreNull($domainObject)
    {
        if ($domainObject === null) {
            return null;
        }

        return $domainObject instanceof DomainObjectReference
            ? $domainObject->getIdentifier()
            : $this->objectIdAccessor->getId($domainObject);
    }

    /**
     * Gets the real class name for the given domain object or the given class name that could be a proxy
     *
     * @param  object|string $domainObjectOrClassName
     *
     * @return string
     */
    protected function getObjectClass($domainObjectOrClassName)
    {
        return ClassUtils::getRealClass(
            $domainObjectOrClassName instanceof DomainObjectReference
                ? $domainObjectOrClassName->getType()
                : $domainObjectOrClassName
        );
    }

    /**
     * Gets metadata for the given domain object
     *
     * @param object $domainObject
     *
     * @return OwnershipMetadataInterface
     */
    protected function getObjectMetadata($domainObject)
    {
        return $this->ownershipMetadataProvider->getMetadata($this->getObjectClass($domainObject));
    }

    /**
     * Gets owner of the given domain object
     *
     * @param  object $domainObject
     *
     * @return object
     * @throws InvalidDomainObjectException
     */
    protected function getOwner($domainObject)
    {
        try {
            return $this->entityOwnerAccessor->getOwner($domainObject);
        } catch (InvalidEntityException $ex) {
            throw new InvalidDomainObjectException($ex->getMessage(), 0, $ex);
        }
    }

    /**
     * @param null $organization
     *
     * @return int|null|string
     */
    protected function getOrganizationId($organization = null)
    {
        if ($organization) {
            return $this->getObjectId($organization);
        }

        return null;
    }

    /**
     * @param object $user
     * @param object $domainObject
     * @param object|null $organization
     * @return int[]|string[]
     */
    protected function getAllowedOrganizationIds($user, $domainObject, $organization = null)
    {
        $organizationId = null;
        if ($organization) {
            $organizationId = $this->getOrganizationId($organization);
        }

        if ($organizationId) {
            return [$organizationId];
        }

        $tree = $this->treeProvider->getTree();

        $userOrganizationIds = $tree->getUserOrganizationIds($this->getObjectId($user));

        return $userOrganizationIds;
    }
}
