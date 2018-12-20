<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Acl\Voter\FieldVote;

/**
 * Provides a functionality to find ACL extension.
 */
class AclExtensionSelector
{
    /** @var ObjectIdAccessor */
    protected $objectIdAccessor;

    /** @var AclExtensionInterface[] */
    protected $extensions = [];

    /** @var array [cache key => ACL extension, ...] */
    protected $localCache = [];

    /**
     * @param ObjectIdAccessor $objectIdAccessor
     */
    public function __construct(ObjectIdAccessor $objectIdAccessor)
    {
        $this->objectIdAccessor = $objectIdAccessor;
    }

    /**
     * Adds ACL extension
     *
     * @param AclExtensionInterface $extension
     */
    public function addAclExtension(AclExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * Gets ACL extension by its key
     *
     * @param string $extensionKey
     *
     * @return AclExtensionInterface|null
     */
    public function selectByExtensionKey($extensionKey)
    {
        foreach ($this->extensions as $extension) {
            if ($extension->getExtensionKey() === $extensionKey) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * Gets ACL extension responsible for work with the given domain object
     *
     * @param mixed $val            A domain object, ObjectIdentity, object identity descriptor (id:type)
     *                              or ACL annotation
     * @param bool  $throwException Whether to throw exception in case the entity has several identifier fields
     *
     * @return AclExtensionInterface|null
     *
     * @throws InvalidDomainObjectException if ACL extension was not found for the given domain object
     *                                      and $throwException is requested (default behaviour)
     */
    public function select($val, $throwException = true)
    {
        if ($val === null) {
            return new NullAclExtension();
        }

        $type = $id = $fieldName = null;
        if (is_string($val)) {
            list($id, $type, $fieldName) = ObjectIdentityHelper::parseIdentityString($val);
        } elseif (is_object($val)) {
            list($id, $type, $fieldName) = $this->parseObject($val);
        }

        $result = null;
        if ($type !== null) {
            $cacheKey = ((string)$id) . '!' . $type;
            if (array_key_exists($cacheKey, $this->localCache)) {
                $result = $this->localCache[$cacheKey];
            } else {
                $result = $this->findExtension($type, $id);
                $this->localCache[$cacheKey] = $result;
            }
            if ($fieldName && null !== $result) {
                $result = $result->getFieldExtension();
                if (!$result->supports($type, $id)) {
                    $result = null;
                }
            }
        }

        if ($throwException && null === $result) {
            throw $this->createAclExtensionNotFoundException($val, $type, $id, $fieldName);
        }

        return $result;
    }

    /**
     * Gets all ACL extension
     *
     * @return AclExtensionInterface[]
     */
    public function all()
    {
        return $this->extensions;
    }

    /**
     * @param object $object
     *
     * @return array [id, type, field name]
     */
    protected function parseObject($object)
    {
        $fieldName = null;
        if ($object instanceof FieldVote) {
            $fieldName = $object->getField();
            $object = $object->getDomainObject();
        }
        if ($object instanceof DomainObjectWrapper) {
            $object = $object->getObjectIdentity();
        }
        if ($object instanceof ObjectIdentityInterface) {
            $id = $object->getIdentifier();
            $type = $object->getType();
        } elseif ($object instanceof AclAnnotation) {
            $id = $object->getType();
            $type = $object->getClass();
            if (empty($type)) {
                $type = $object->getId();
            }
        } else {
            try {
                $id = $this->objectIdAccessor->getId($object);
                $type = ClassUtils::getRealClass($object);
            } catch (\Throwable $e) {
                $id = null;
                $type = null;
            }
        }

        return [$id, $type, $fieldName];
    }

    /**
     * @param string $type
     * @param mixed  $id
     *
     * @return AclExtensionInterface|null
     */
    protected function findExtension($type, $id)
    {
        foreach ($this->extensions as $extension) {
            if ($extension->supports($type, $id)) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * Creates an exception indicates that ACL extension was not found for the given domain object
     *
     * @param mixed       $val
     * @param string      $type
     * @param int|string  $id
     * @param string|null $fieldName
     *
     * @return InvalidDomainObjectException
     */
    protected function createAclExtensionNotFoundException($val, $type, $id, $fieldName = null)
    {
        $objInfo = is_object($val) && !($val instanceof ObjectIdentityInterface)
            ? get_class($val)
            : (string)$val;

        $message = sprintf(
            'An ACL extension was not found for: %s. Type: %s. Id: %s.',
            $objInfo,
            $type,
            (string)$id
        );
        if ($fieldName) {
            $message .= sprintf(' Field: %s.', $fieldName);
        }

        return new InvalidDomainObjectException($message);
    }
}
