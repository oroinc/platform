<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;

/**
 * This class provides a functionality to find ACL extension
 */
class AclExtensionSelector
{
    /**
     * @var ObjectIdAccessor
     */
    protected $objectIdAccessor;

    /**
     * @var AclExtensionInterface[]
     */
    protected $extensions = array();

    /**
     * @var array
     * key = a string unique for each ObjectIdentity
     * value = ACL extension
     */
    protected $localCache = array();

    /**
     * Constructor
     *
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
     * @param mixed $val A domain object, ObjectIdentity, object identity descriptor (id:type) or ACL annotation
     * @throws InvalidDomainObjectException
     * @return AclExtensionInterface
     */
    public function select($val)
    {
        if ($val === null) {
            return new NullAclExtension();
        }

        $type = $id = null;
        if (is_string($val)) {
            $delim = strpos($val, ':');
            if ($delim) {
                $type = ltrim(substr($val, $delim + 1), ' ');
                $id = strtolower(substr($val, 0, $delim));
            }
        } elseif (is_object($val)) {
            $object = $val;
            if ($val instanceof FieldVote) {
                $object = $val->getDomainObject();
            }

            if ($object instanceof ObjectIdentityInterface) {
                $type = $object->getType();
                $id = $object->getIdentifier();
            } elseif ($object instanceof AclAnnotation) {
                $type = $object->getClass();
                if (empty($type)) {
                    $type = $object->getId();
                }
                $id = $object->getType();
            } else {
                $type = get_class($object);
                $id = $this->objectIdAccessor->getId($object);
            }
        }

        if ($type !== null) {
            $cacheKey = ($id ?: 'null') . '!' . $type;
            if (isset($this->localCache[$cacheKey])) {
                return $this->localCache[$cacheKey]->getExtensionInstanceForObject($val);
            }

            foreach ($this->extensions as $extension) {
                if ($extension->supports($type, $id)) {
                    $this->localCache[$cacheKey] = $extension;

                    return $extension->getExtensionInstanceForObject($val);
                }
            }
        }

        throw $this->createAclExtensionNotFoundException($val, $type, $id);
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
     * Creates an exception indicates that ACL extension was not found for the given domain object
     *
     * @param mixed $val
     * @param string $type
     * @param int|string $id
     * @return InvalidDomainObjectException
     */
    protected function createAclExtensionNotFoundException($val, $type, $id)
    {
        $objInfo = is_object($val) && !($val instanceof ObjectIdentityInterface)
            ? get_class($val)
            : (string)$val;

        return new InvalidDomainObjectException(
            sprintf('An ACL extension was not found for: %s. Type: %s. Id: %s', $objInfo, $type, (string)$id)
        );
    }
}
