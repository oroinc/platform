<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\AclVoter as BaseAclVoter;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;

/**
 * This voter uses ACL to determine whether the access to the particular resource is granted or not.
 */
class AclVoter extends BaseAclVoter implements PermissionGrantingStrategyContextInterface
{
    /**
     * @var AclExtensionSelector
     */
    protected $extensionSelector;

    /**
     * An object which is the subject of the current voting operation
     *
     * @var mixed
     */
    private $object = null;

    /**
     * The security token of the current voting operation
     *
     * @var mixed
     */
    private $securityToken = null;

    /**
     * An ACL extension responsible to process an object of the current voting operation
     *
     * @var AclExtensionInterface
     */
    private $extension = null;

    /**
     * @var OneShotIsGrantedObserver|OneShotIsGrantedObserver[]
     */
    protected $oneShotIsGrantedObserver = null;

    /**
     * @var int
     */
    protected $triggeredMask;

    /**
     * @var EntityOwnerAccessor
     */
    protected $entityOwnerAccessor;

    /**
     * @var AclGroupProviderInterface
     */
    protected $groupProvider;

    /**
     * @param EntityOwnerAccessor $entityOwnerAccessor
     */
    public function setEntityOwnerAccessor(EntityOwnerAccessor $entityOwnerAccessor)
    {
        $this->entityOwnerAccessor = $entityOwnerAccessor;
    }

    /**
     * Sets the ACL extension selector
     *
     * @param AclExtensionSelector $selector
     */
    public function setAclExtensionSelector(AclExtensionSelector $selector)
    {
        $this->extensionSelector = $selector;
    }

    /**
     * @param AclGroupProviderInterface $provider
     */
    public function setAclGroupProvider(AclGroupProviderInterface $provider)
    {
        $this->groupProvider = $provider;
    }

    /**
     * Adds an observer is used to inform a caller about IsGranted operation details
     *
     * @param OneShotIsGrantedObserver $observer
     */
    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer)
    {
        if ($this->oneShotIsGrantedObserver !== null) {
            if (!is_array($this->oneShotIsGrantedObserver)) {
                $this->oneShotIsGrantedObserver = array($this->oneShotIsGrantedObserver);
            }
            $this->oneShotIsGrantedObserver[] = $observer;
        } else {
            $this->oneShotIsGrantedObserver = $observer;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->securityToken = $token;
        $this->object = $object instanceof FieldVote
            ? $object->getDomainObject()
            : $object;

        list($this->object, $group) = $this->separateAclGroupFromObject($this->object);

        try {
            $this->extension = $this->extensionSelector->select($object);
        } catch (InvalidDomainObjectException $e) {
            return self::ACCESS_ABSTAIN;
        }

        // replace empty permissions with default ones
        $attributesCount = count($attributes);
        for ($i = 0; $i < $attributesCount; $i++) {
            if (empty($attributes[$i])) {
                $attributes[$i] = $this->extension->getDefaultPermission();
            }
        }

        //check acl group
        $result = $this->checkAclGroup($group);

        if ($result !== self::ACCESS_DENIED) {
            $result = parent::vote($token, $object, $attributes);

            //check organization context
            $result = $this->checkOrganizationContext($result);
        }

        $this->extension = null;
        $this->object = null;
        $this->securityToken = null;
        $this->triggeredMask = null;
        if ($this->oneShotIsGrantedObserver) {
            $this->oneShotIsGrantedObserver = null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityToken()
    {
        return $this->securityToken;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclExtension()
    {
        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function setTriggeredMask($mask)
    {
        $this->triggeredMask = $mask;
        if ($this->oneShotIsGrantedObserver !== null) {
            if (is_array($this->oneShotIsGrantedObserver)) {
                /** @var OneShotIsGrantedObserver $observer */
                foreach ($this->oneShotIsGrantedObserver as $observer) {
                    $observer->setAccessLevel($this->extension->getAccessLevel($mask, null, $this->object));
                }
            } else {
                $this->oneShotIsGrantedObserver->setAccessLevel(
                    $this->extension->getAccessLevel($mask, null, $this->object)
                );
            }
        }
    }

    /**
     * Check organization. If user try to access entity what was created in organization this user do not have access -
     *  deny access. We should check organization for all the entities what have ownership
     *  (USER, BUSINESS_UNIT, ORGANIZATION ownership types)
     *
     * @param int $result
     * @return int
     */
    protected function checkOrganizationContext($result)
    {
        $object = $this->object;
        $token = $this->securityToken;

        if ($token instanceof OrganizationContextTokenInterface
            && $result === self::ACCESS_GRANTED
            && $this->extension instanceof EntityAclExtension
            && is_object($object)
            && !($object instanceof ObjectIdentity)
        ) {
            try {
                // try to get entity organization value
                $objectOrganization = $this->entityOwnerAccessor->getOrganization($object);

                // check entity organization with current organization
                if ($objectOrganization
                    && $objectOrganization->getId() !== $token->getOrganizationContext()->getId()
                ) {
                    $result = self::ACCESS_DENIED;
                }
            } catch (InvalidEntityException $e) {
                // in case if entity has no organization field (none ownership type)
                return $result;
            }
        }

        return $result;
    }

    /**
     * @param mixed $object
     * @return array
     */
    protected function separateAclGroupFromObject($object)
    {
        $group = null;

        if ($object instanceof ObjectIdentity) {
            $type = $object->getType();

            $delim = strpos($type, '@');
            if ($delim) {
                $object = new ObjectIdentity($this->object->getIdentifier(), ltrim(substr($type, $delim + 1), ' '));
                $group = ltrim(substr($type, 0, $delim), ' ');
            } else {
                $group = AclGroupProviderInterface::DEFAULT_SECURITY_GROUP;
            }
        }

        return [$object, $group];
    }

    /**
     * @param string $group
     * @return int
     */
    protected function checkAclGroup($group)
    {
        if ($group=== null || !$this->groupProvider || !$this->object) {
            return self::ACCESS_ABSTAIN;
        }

        return $group === $this->groupProvider->getGroup() ? self::ACCESS_ABSTAIN : self::ACCESS_DENIED;
    }
}
