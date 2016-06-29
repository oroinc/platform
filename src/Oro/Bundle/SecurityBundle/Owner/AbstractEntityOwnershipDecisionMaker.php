<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Acl\Domain\EntityObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;

/**
 * This class implements AccessLevelOwnershipDecisionMakerInterface interface and allows to make ownership related
 * decisions using the tree of owners.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractEntityOwnershipDecisionMaker implements
    AccessLevelOwnershipDecisionMakerInterface,
    ContainerAwareInterface
{
    /**
     * @var OwnerTreeProvider
     */
    private $treeProvider;

    /**
     * @var ObjectIdAccessor
     */
    private $objectIdAccessor;

    /**
     * @var EntityOwnerAccessor
     */
    private $entityOwnerAccessor;

    /**
     * @var OwnershipMetadataProvider
     */
    private $metadataProvider;

    /**
     * @var ContainerAwareInterface
     */
    private $container;

    /**
     * @return OwnershipMetadataProvider
     */
    public function getMetadataProvider()
    {
        if (!$this->metadataProvider) {
            $this->metadataProvider = $this->getContainer()->get('oro_security.owner.metadata_provider.chain');
        }

        return $this->metadataProvider;
    }

    /**
     * @return OwnerTreeProvider
     */
    public function getTreeProvider()
    {
        if (!$this->treeProvider) {
            $this->treeProvider = $this->getContainer()->get('oro_security.ownership_tree_provider.chain');
        }

        return $this->treeProvider;
    }

    /**
     * @return ObjectIdAccessor
     */
    public function getObjectIdAccessor()
    {
        if (!$this->objectIdAccessor) {
            $this->objectIdAccessor = $this->getContainer()->get('oro_security.acl.object_id_accessor');
        }

        return $this->objectIdAccessor;
    }

    /**
     * @return EntityOwnerAccessor
     */
    public function getEntityOwnerAccessor()
    {
        if (!$this->entityOwnerAccessor) {
            $this->entityOwnerAccessor = $this->getContainer()->get('oro_security.owner.entity_owner_accessor');
        }

        return $this->entityOwnerAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface is not injected');
        }

        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function isGlobalLevelEntity($domainObject)
    {
        return is_a(
            $domainObject instanceof EntityObjectReference ? $domainObject->getType() : $domainObject,
            $this->getMetadataProvider()->getGlobalLevelClass(),
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalLevelEntity($domainObject)
    {
        return is_a(
            $domainObject instanceof EntityObjectReference ? $domainObject->getType() : $domainObject,
            $this->getMetadataProvider()->getLocalLevelClass(),
            true
        );
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function isBasicLevelEntity($domainObject)
    {
        return is_a(
            $domainObject instanceof EntityObjectReference ? $domainObject->getType() : $domainObject,
            $this->getMetadataProvider()->getBasicLevelClass(),
            true
        );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function isAssociatedWithGlobalLevelEntity($user, $domainObject, $organization = null)
    {
        $tree = $this->getTreeProvider()->getTree();
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

        $allowedOrganizationIds = $organizationId ? [$organizationId] : $userOrganizationIds;

        if ($this->isGlobalLevelEntity($domainObject)) {
            return in_array(
                $this->getObjectId($domainObject),
                $allowedOrganizationIds,
                true
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
            return $organizationId ? $ownerId === $organizationId : in_array($ownerId, $userOrganizationIds, true);
        }

        $ownerOrganization = $this->getEntityOwnerAccessor()->getOrganization($domainObject);

        // in case when entity has no owner yet (e.g. checking for new object)
        $noOwnerExistsYet = is_null($ownerOrganization);

        return $noOwnerExistsYet || in_array(
            $this->getObjectId($ownerOrganization),
            $allowedOrganizationIds,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isAssociatedWithLocalLevelEntity($user, $domainObject, $deep = false, $organization = null)
    {
        $tree = $this->getTreeProvider()->getTree();
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

        $ownerId = $domainObject instanceof EntityObjectReference ?
            $domainObject->getOwnerId() :
            $this->getObjectIdIgnoreNull($this->getOwner($domainObject));
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
     */
    public function isAssociatedWithBasicLevelEntity($user, $domainObject, $organization = null)
    {
        $userId = $this->getObjectId($user);
        if ($organization
            && !in_array(
                $this->getObjectId($organization),
                $this->getTreeProvider()->getTree()->getUserOrganizationIds($userId),
                true
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
            $ownerId = $domainObject instanceof EntityObjectReference ?
                $domainObject->getOwnerId() :
                $this->getObjectIdIgnoreNull($this->getOwner($domainObject));

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
        $userBusinessUnitIds = $this->getTreeProvider()->getTree()->getUserBusinessUnitIds($userId, $organizationId);
        $familiarBusinessUnits = array_intersect($userBusinessUnitIds, $ownerBusinessUnitIds);
        if (!empty($familiarBusinessUnits)) {
            return true;
        }
        if ($deep) {
            foreach ($userBusinessUnitIds as $buId) {
                $familiarBusinessUnits = array_intersect(
                    $this->getTreeProvider()->getTree()->getSubordinateBusinessUnitIds($buId),
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

        $businessUnits = $this->getTreeProvider()->getTree()->getUserBusinessUnitIds($userId, $organizationId);
        foreach ($businessUnits as $buId) {
            if ($businessUnitId === $buId) {
                return true;
            }
            if ($deep
                && in_array(
                    $businessUnitId,
                    $this->getTreeProvider()->getTree()->getSubordinateBusinessUnitIds($buId),
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
     * @throws InvalidDomainObjectException
     */
    protected function validateUserObject($user)
    {
        if (!is_object($user) || !$this->isBasicLevelEntity($user)) {
            throw new InvalidDomainObjectException(
                sprintf(
                    '$user must be an instance of %s.',
                    $this->getMetadataProvider()->getBasicLevelClass()
                )
            );
        }
    }

    /**
     * Check that the given object is a domain object
     *
     * @param  object $domainObject
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
     * @return int|string
     * @throws InvalidDomainObjectException
     */
    protected function getObjectId($domainObject)
    {
        return $domainObject instanceof EntityObjectReference ?
            $domainObject->getIdentifier() :
            $this->getObjectIdAccessor()->getId($domainObject);
    }

    /**
     * Gets id for the given domain object.
     * Returns null when the given domain object is null
     *
     * @param  object|null $domainObject
     * @return int|string|null
     * @throws InvalidDomainObjectException
     */
    protected function getObjectIdIgnoreNull($domainObject)
    {
        if ($domainObject === null) {
            return null;
        }

        return $domainObject instanceof EntityObjectReference ?
            $domainObject->getIdentifier() :
            $this->getObjectIdAccessor()->getId($domainObject);
    }

    /**
     * Gets the real class name for the given domain object or the given class name that could be a proxy
     *
     * @param  object|string $domainObjectOrClassName
     * @return string
     */
    protected function getObjectClass($domainObjectOrClassName)
    {
        return ClassUtils::getRealClass(
            $domainObjectOrClassName instanceof EntityObjectReference ?
            $domainObjectOrClassName->getType() :
            $domainObjectOrClassName
        );
    }

    /**
     * Gets metadata for the given domain object
     *
     * @param  object $domainObject
     * @return OwnershipMetadata
     */
    protected function getObjectMetadata($domainObject)
    {
        return $this->getMetadataProvider()->getMetadata($this->getObjectClass($domainObject));
    }

    /**
     * Gets owner of the given domain object
     *
     * @param  object $domainObject
     * @return object
     * @throws InvalidDomainObjectException
     */
    protected function getOwner($domainObject)
    {
        try {
            return $this->getEntityOwnerAccessor()->getOwner($domainObject);
        } catch (InvalidEntityException $ex) {
            throw new InvalidDomainObjectException($ex->getMessage(), 0, $ex);
        }
    }

    /**
     * @param null $organization
     * @return int|null|string
     */
    protected function getOrganizationId($organization = null)
    {
        if ($organization) {
            return $this->getObjectId($organization);
        }

        return null;
    }
}
