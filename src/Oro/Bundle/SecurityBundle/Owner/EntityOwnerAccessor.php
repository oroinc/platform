<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;

/**
 * This class allows to get the owner of an entity
 */
class EntityOwnerAccessor
{
    /**
     * @var OwnershipMetadataProvider
     */
    protected $metadataProvider;

    /**
     * Constructor
     *
     * @param OwnershipMetadataProvider $metadataProvider
     */
    public function __construct(OwnershipMetadataProvider $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * Gets owner of the given entity
     *
     * @param object $object
     * @return object
     * @throws \RuntimeException
     */
    public function getOwner($object)
    {
        if (!is_object($object)) {
            throw new InvalidEntityException('$object must be an object.');
        }

        $result = null;
        $metadata = $this->metadataProvider->getMetadata(ClassUtils::getClass($object));
        if ($metadata->hasOwner()) {
            // at first try to use getOwner method to get the owner
            if (method_exists($object, 'getOwner')) {
                $result = $object->getOwner();
            } else {
                // if getOwner method does not exist try to get owner directly from field
                try {
                    $cls = new \ReflectionClass($object);
                    $ownerProp = $cls->getProperty($metadata->getOwnerFieldName());
                    if (!$ownerProp->isPublic()) {
                        $ownerProp->setAccessible(true);
                    }
                    $result = $ownerProp->getValue($object);
                } catch (\ReflectionException $ex) {
                    throw new InvalidEntityException(
                        sprintf(
                            '$object must have either "getOwner" method or "%s" property.',
                            $metadata->getOwnerFieldName()
                        ),
                        0,
                        $ex
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Gets organization of the given entity
     *
     * @param $object
     * @return object
     * @throws InvalidEntityException
     */
    public function getOrganization($object)
    {
        if (!is_object($object)) {
            throw new InvalidEntityException('$object must be an object.');
        }

        $result = null;
        $metadata = $this->metadataProvider->getMetadata(ClassUtils::getClass($object));
        if ($metadata->getOrganizationFieldName()) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $result = $accessor->getValue($object, $metadata->getOrganizationFieldName());
        }

        return $result;
    }
}
