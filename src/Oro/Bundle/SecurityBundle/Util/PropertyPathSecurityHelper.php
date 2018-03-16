<?php

namespace Oro\Bundle\SecurityBundle\Util;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PropertyPathSecurityHelper
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ManagerRegistry               $managerRegistry
     * @param ConfigProvider                $entityConfigProvider
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->managerRegistry = $managerRegistry;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * Check access by given property path. Would be checked all the property path parts.
     * For example, if $propertyPath = 'firstLevelRelation.secondLevelRelation.someField',
     * the next checks will be processes:
     *  - check field access to 'firstLevelRelation' field for given $object
     *  - check object access to the 'firstLevelRelation' object value
     *  - check field access to 'secondLevelRelation' field for the 'firstLevelRelation' object value
     *  - check object access to the 'secondLevelRelation' object value
     *  - check field access to 'someField' field for the 'secondLevelRelation' object value
     *
     * In case if on the some step we will have no object, access will be checked on class level.
     *
     * @param object $object
     * @param string $propertyPath
     * @param string $permission
     *
     * @return bool
     */
    public function isGrantedByPropertyPath($object, $propertyPath, $permission = 'VIEW')
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyPath = new PropertyPath($propertyPath);
        $pathElements = array_values($propertyPath->getElements());
        $stepsCount = $propertyPath->getLength();

        // prepare data for the first check iteration
        $className = ClassUtils::getClass($object);
        $metadata = $this->getMetadataForClass($className);

        foreach ($pathElements as $id => $field) {
            // check access on field level
            if (!$this->checkIsGranted($permission, $object, $className, $field)) {
                return false;
            }

            $hasAssociation = $metadata->hasAssociation($field)
                || $this->entityConfigProvider->hasConfig($className, $field);

            // check access on object level in case if current step if not final and prepare data
            // for the next step
            if ($hasAssociation && ($stepsCount - 1) !== $id) {
                // get object from the relation to make checks on it
                $object = $propertyAccessor->getValue($object, $field);
                if (!$this->checkIsGranted($permission, $object, $className)
                ) {
                    return false;
                }

                // prepare data for the next step
                $className = $metadata->hasAssociation($field)
                    ? $metadata->getAssociationTargetClass($field)
                    : $this->entityConfigProvider->getConfig($className, $field)->getId()->getClassName();
                $metadata = $this->getMetadataForClass($className);
            }
        }

        return true;
    }

    /**
     * Check access for given attributes
     *
     * @param string $permission
     * @param object $object
     * @param string $className
     * @param string $field
     *
     * @return bool
     */
    protected function checkIsGranted($permission, $object = null, $className = null, $field = null)
    {
        // in case if we have no object, check access on class level
        $object = $object ?: new ObjectIdentity('entity', $className);

        // in case if we have field, check Field access
        $object = $field ? new FieldVote($object, $field) : $object;

        return $this->authorizationChecker->isGranted($permission, $object);
    }

    /**
     * @param string $class
     *
     * @return ClassMetadata
     * @throws \Exception
     */
    protected function getMetadataForClass($class)
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager) {
            throw new \InvalidArgumentException(sprintf('Can\'t get entity manager for class %s', $class));
        }

        return $entityManager->getClassMetadata($class);
    }
}
