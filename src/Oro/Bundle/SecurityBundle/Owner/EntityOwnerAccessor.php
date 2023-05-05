<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
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

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    private Inflector $inflector;

    public function __construct(OwnershipMetadataProviderInterface $metadataProvider, Inflector $inflector)
    {
        $this->metadataProvider = $metadataProvider;
        $this->inflector = $inflector;
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
                $reflectionClass = new EntityReflectionClass($object);
                $reflectionProperty = $reflectionClass->getProperty($property);
                $reflectionProperty->setAccessible(true);

                return $reflectionProperty->getValue($object);
            } catch (\ReflectionException $ex) {
                throw new InvalidEntityException(
                    sprintf(
                        '$object must have either "%s" method or "%s" property.',
                        $this->inflector->camelize(sprintf('get_%s', $property)),
                        $property
                    ),
                    0,
                    $ex
                );
            }
        }
    }
}
