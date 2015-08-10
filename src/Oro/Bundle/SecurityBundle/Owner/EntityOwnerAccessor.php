<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Util\Inflector;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;

/**
 * This class allows to get the owner of an entity
 */
class EntityOwnerAccessor
{
    /**
     * @var MetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * Constructor
     *
     * @param MetadataProviderInterface $metadataProvider
     */
    public function __construct(MetadataProviderInterface $metadataProvider)
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
        if ($metadata->getGlobalOwnerFieldName()) {
            return $this->getValue($object, $metadata->getGlobalOwnerFieldName());
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
