<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Util\Inflector;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * This class allows to get the owner of an entity
 */
class EntityOwnerAccessor
{
    /**
     * @var OwnershipMetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * Constructor
     *
     * @param OwnershipMetadataProviderInterface $metadataProvider
     */
    public function __construct(OwnershipMetadataProviderInterface $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * Gets owner of the given entity
     *
     * @param mixed $object
     * @return mixed
     * @throws InvalidEntityException     If entity is not an object
     * @throws \InvalidArgumentException  If owner property path is not defined
     */
    public function getOwner($object)
    {
        if (!is_object($object)) {
            throw new InvalidEntityException('$object must be an object.');
        }

        $metadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($object));
        if ($metadata->hasOwner() && $metadata->getOwnerFieldName()) {
            return $this->getValue($object, $metadata->getOwnerFieldName());
        }

        return null;
    }

    /**
     * Gets organization of the given entity
     *
     * @param mixed $object
     * @return mixed
     * @throws InvalidEntityException     If entity is not an object
     * @throws \InvalidArgumentException  If owner property path is not defined
     */
    public function getOrganization($object)
    {
        if (!is_object($object)) {
            throw new InvalidEntityException('$object must be an object.');
        }

        $metadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($object));
        if ($metadata->getOrganizationFieldName()) {
            return $this->getValue($object, $metadata->getOrganizationFieldName());
        }

        return null;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    protected function getValue($object, $property)
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            return $this->propertyAccessor->getValue($object, $property);
        } catch (NoSuchPropertyException $e) {
            try {
                $reflectionClass = new \ReflectionClass($object);
                $reflectionProperty = $reflectionClass->getProperty($property);
                $reflectionProperty->setAccessible(true);

                return $reflectionProperty->getValue($object);
            } catch (\ReflectionException $ex) {
                throw new InvalidEntityException(
                    sprintf(
                        '$object must have either "%s" method or "%s" property.',
                        Inflector::camelize(sprintf('get_%s', $property)),
                        $property
                    ),
                    0,
                    $ex
                );
            }
        }
    }
}
