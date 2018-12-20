<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclException;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * The factory that is intended to create ObjectIdentity objects.
 */
class ObjectIdentityFactory
{
    public const ROOT_IDENTITY_TYPE = '(root)';

    /** @var AclExtensionSelector */
    private $extensionSelector;

    /**
     * @param AclExtensionSelector $extensionSelector
     */
    public function __construct(AclExtensionSelector $extensionSelector)
    {
        $this->extensionSelector = $extensionSelector;
    }

    /**
     * Constructs an ObjectIdentity object is used for grant default permissions
     * if more appropriate permissions are not specified.
     *
     * @param ObjectIdentity|string $oidOrExtensionKey Can be an instance of ObjectIdentity
     *                                                 or a string represents the key of an ACL extension
     *
     * @return ObjectIdentity
     */
    public function root($oidOrExtensionKey): ObjectIdentity
    {
        if ($oidOrExtensionKey instanceof ObjectIdentityInterface) {
            $oidOrExtensionKey = $this->extensionSelector
                ->select($oidOrExtensionKey)
                ->getExtensionKey();
        } else {
            $oidOrExtensionKey = strtolower($oidOrExtensionKey);
        }

        return new ObjectIdentity($oidOrExtensionKey, static::ROOT_IDENTITY_TYPE);
    }

    /**
     * Constructs an underlying ObjectIdentity object for given ObjectIdentity.
     * Underlying is class level ObjectIdentity for given object level ObjectIdentity.
     *
     * @param ObjectIdentityInterface $oid
     *
     * @return ObjectIdentity
     *
     * @throws InvalidAclException if an underlying ObjectIdentity cannot be created
     */
    public function underlying(ObjectIdentityInterface $oid): ObjectIdentity
    {
        $id = $oid->getIdentifier();
        if (self::ROOT_IDENTITY_TYPE === $id
            || $id === ($extensionKey = $this->extensionSelector->select($oid)->getExtensionKey())
        ) {
            throw new InvalidAclException(sprintf(
                'Cannot get underlying ACL for %s.',
                $this->convertObjectIdentityToString($oid)
            ));
        }

        return new ObjectIdentity($extensionKey, $oid->getType());
    }

    /**
     * Constructs an ObjectIdentity for the given domain object or based on the given descriptor.
     *
     * The descriptor is a string in the following format: "ExtensionKey:Class"
     *
     * Examples:
     *     get($object)
     *     get('Entity:AcmeBundle\SomeClass')
     *     get('Entity:AcmeBundle:SomeEntity')
     *     get('Action:Some Action')
     *
     * @param mixed $val An domain object, object identity descriptor (id:type) or ACL annotation
     *
     * @return ObjectIdentity
     *
     * @throws InvalidDomainObjectException if the object identity cannot be created for the given value
     */
    public function get($val): ObjectIdentity
    {
        $extension = $this->getAclExtension($val);
        if (null === $extension) {
            throw new InvalidDomainObjectException(sprintf(
                'Cannot create ObjectIdentity for "%s" because suitable ACL extension was not found.',
                is_object($val) ? get_class($val) : (string)$val
            ));
        }

        try {
            return $extension->getObjectIdentity($val);
        } catch (\InvalidArgumentException $ex) {
            throw new InvalidDomainObjectException($ex->getMessage(), 0, $ex);
        }
    }

    /**
     * @param mixed $val
     *
     * @return AclExtensionInterface|null
     */
    private function getAclExtension($val): ?AclExtensionInterface
    {
        if ($val instanceof Acl) {
            return $this->extensionSelector->selectByExtensionKey($val->getType());
        }

        if (\is_string($val) && ObjectIdentityHelper::isEncodedIdentityString($val)) {
            return $this->extensionSelector->selectByExtensionKey(
                ObjectIdentityHelper::getExtensionKeyFromIdentityString($val)
            );
        }

        return $this->extensionSelector->select($val, false);
    }

    /**
     * @param ObjectIdentityInterface $oid
     *
     * @return string
     */
    private function convertObjectIdentityToString(ObjectIdentityInterface $oid): string
    {
        return method_exists($oid, '__toString')
            ? (string)$oid
            : sprintf('%s(%s, %s)', get_class($oid), $oid->getIdentifier(), $oid->getType());
    }
}
