<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides a way to find ACL extension.
 */
class AclExtensionSelector implements ResetInterface
{
    private array $extensionNames;
    private ContainerInterface $extensionContainer;
    private ObjectIdAccessor $objectIdAccessor;

    /** @var AclExtensionInterface[]|null [ACL extension key => ACL extension, ...] */
    private ?array $extensions = null;
    /** @var array [cache key => ACL extension or NULL, ...] */
    private $localCache = [];

    public function __construct(
        array $extensionNames,
        ContainerInterface $extensionContainer,
        ObjectIdAccessor $objectIdAccessor
    ) {
        $this->extensionNames = $extensionNames;
        $this->extensionContainer = $extensionContainer;
        $this->objectIdAccessor = $objectIdAccessor;
    }

    #[\Override]
    public function reset()
    {
        $this->localCache = [];
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
        return $this->all()[$extensionKey] ?? null;
    }

    /**
     * Gets ACL extension responsible for work with the given domain object
     *
     * @param mixed $val            A domain object, ObjectIdentity, object identity descriptor (id:type)
     *                              or ACL attribute
     * @param bool  $throwException Whether to throw exception in case the entity has several identifier fields
     *
     * @return AclExtensionInterface|null
     *
     * @throws InvalidDomainObjectException if ACL extension was not found for the given domain object
     *                                      and $throwException is requested (default behaviour)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function select($val, $throwException = true)
    {
        if ($val === null) {
            return new NullAclExtension();
        }

        $type = $id = $fieldName = null;
        if (\is_string($val)) {
            [$id, $type, $fieldName] = ObjectIdentityHelper::parseIdentityString($val);
        } elseif (\is_object($val)) {
            [$id, $type, $fieldName] = $this->parseObject($val);
        }

        $result = null;
        if ($type !== null) {
            $cacheKey = ((string)$id) . '!' . $type;
            if (\array_key_exists($cacheKey, $this->localCache)) {
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
     * Gets all ACL extensions.
     *
     * @return AclExtensionInterface[] [ACL extension key => ACL extension, ...]
     */
    public function all()
    {
        if (null === $this->extensions) {
            $this->extensions = [];
            foreach ($this->extensionNames as $name) {
                /** @var AclExtensionInterface $extension */
                $extension = $this->extensionContainer->get($name);
                $this->extensions[$extension->getExtensionKey()] = $extension;
            }
        }

        return $this->extensions;
    }

    /**
     * @param object $object
     *
     * @return array [id, type, field name]
     */
    private function parseObject($object)
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
        } elseif ($object instanceof AclAttribute) {
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
    private function findExtension($type, $id)
    {
        $extensions = $this->all();
        foreach ($extensions as $extension) {
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
    private function createAclExtensionNotFoundException($val, $type, $id, $fieldName = null)
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
