<?php

namespace Oro\Bundle\OrganizationBundle\Ownership;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class OwnerDeletionManager
{
    /**
     * @var ConfigProvider
     */
    protected $ownershipProvider;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $ownershipMetadata;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ObjectIdAccessor
     */
    protected $objectIdAccessor;

    /**
     * @var OwnerAssignmentCheckerInterface
     */
    protected $defaultChecker;

    /**
     * @var OwnerAssignmentCheckerInterface[]
     */
    protected $checkers = [];

    /**
     * Constructor
     *
     * @param OwnerAssignmentCheckerInterface $defaultChecker
     * @param ConfigProvider                  $ownershipProvider
     * @param OwnershipMetadataProvider       $ownershipMetadata
     * @param EntityManager                   $em
     * @param ObjectIdAccessor                $objectIdAccessor
     */
    public function __construct(
        OwnerAssignmentCheckerInterface $defaultChecker,
        ConfigProvider $ownershipProvider,
        OwnershipMetadataProvider $ownershipMetadata,
        EntityManager $em,
        ObjectIdAccessor $objectIdAccessor
    ) {
        $this->defaultChecker    = $defaultChecker;
        $this->ownershipProvider = $ownershipProvider;
        $this->ownershipMetadata = $ownershipMetadata;
        $this->em                = $em;
        $this->objectIdAccessor  = $objectIdAccessor;
    }

    /**
     * Registers an object responsible for check owner assignment for the given entity class
     *
     * @param string                          $entityClassName
     * @param OwnerAssignmentCheckerInterface $checker
     */
    public function registerAssignmentChecker($entityClassName, OwnerAssignmentCheckerInterface $checker)
    {
        $this->checkers[$entityClassName] = $checker;
    }

    /**
     * Determines whether the given entity is an owner.
     *
     * @param object $entity
     *
     * @return bool true if the given entity is User, BusinessUnit or Organization; otherwise, false
     */
    public function isOwner($entity)
    {
        return $this->getOwnerType($entity) !== OwnershipType::OWNER_TYPE_NONE;
    }

    /**
     * Checks if the given owner owns at least one entity
     *
     * @param object $owner
     *
     * @return bool
     */
    public function hasAssignments($owner)
    {
        $ownerType = $this->getOwnerType($owner);
        if ($ownerType !== OwnershipType::OWNER_TYPE_NONE) {
            foreach ($this->ownershipProvider->getConfigs(null, true) as $config) {
                if ($config->get('owner_type') === $ownerType) {
                    $entityClassName = $config->getId()->getClassName();
                    $result = $this->getAssignmentChecker($entityClassName)->hasAssignments(
                        $this->objectIdAccessor->getId($owner),
                        $entityClassName,
                        $this->ownershipMetadata->getMetadata($entityClassName)->getOwnerFieldName(),
                        $this->em
                    );
                    if ($result) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if the given organization owns at least one entity
     *
     * @param Organization $organization
     *
     * @return bool
     */
    public function hasOrganizationAssignments(Organization $organization)
    {
        foreach ($this->ownershipProvider->getConfigs(null, true) as $config) {
            if (in_array(
                $config->get('owner_type'),
                [OwnershipType::OWNER_TYPE_USER, OwnershipType::OWNER_TYPE_BUSINESS_UNIT],
                true
            )) {
                $entityClassName = $config->getId()->getClassName();
                $organizationFieldName = $this->ownershipMetadata
                    ->getMetadata($entityClassName)
                    ->getGlobalOwnerFieldName();
                $findResult = $this->em->getRepository($entityClassName)
                    ->createQueryBuilder('entity')
                    ->select('organization.id')
                    ->innerJoin(sprintf('entity.%s', $organizationFieldName), 'organization')
                    ->where('organization.id = :ownerId')
                    ->setParameter('ownerId', $this->objectIdAccessor->getId($organization))
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getArrayResult();

                if ($findResult) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Gets an instance of OwnerAssignmentCheckerInterface responsible
     * for check owner assignment for the given entity class
     *
     * @param string $entityClassName
     *
     * @return OwnerAssignmentCheckerInterface
     */
    protected function getAssignmentChecker($entityClassName)
    {
        return isset($this->checkers[$entityClassName])
            ? $this->checkers[$entityClassName]
            : $this->defaultChecker;
    }

    /**
     * Gets a string represents the type of the given owner
     *
     * @param mixed $owner
     *
     * @return string
     */
    protected function getOwnerType($owner)
    {
        if (is_a($owner, $this->ownershipMetadata->getUserClass())) {
            return OwnershipType::OWNER_TYPE_USER;
        }

        if (is_a($owner, $this->ownershipMetadata->getBusinessUnitClass())) {
            return OwnershipType::OWNER_TYPE_BUSINESS_UNIT;
        }

        if (is_a($owner, $this->ownershipMetadata->getOrganizationClass())) {
            return OwnershipType::OWNER_TYPE_ORGANIZATION;
        }

        return OwnershipType::OWNER_TYPE_NONE;
    }
}
