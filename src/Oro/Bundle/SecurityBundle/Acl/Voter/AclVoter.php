<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\AclVoter as BaseAclVoter;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;

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
     * @var AclGroupProviderInterface
     */
    protected $groupProvider;

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
            // select ACL extension based on object given (that could be FieldVote instance)
            //     to be able to choose field ACL extension
            // or based on object that could be created in separateAclGroupFromObject
            $this->extension = $this->extensionSelector->select(
                $object instanceof FieldVote ? $object : $this->object
            );
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
        $result = $this->checkAclGroup($attributes, $group);

        if ($result !== self::ACCESS_DENIED) {
            $result = parent::vote($token, $object, $attributes);
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
     * @param array $attributes
     * @param string $group
     * @return int
     */
    protected function checkAclGroup(array $attributes, $group)
    {
        if ($group === null || !$this->groupProvider || !$this->object) {
            return self::ACCESS_ABSTAIN;
        }

        $result = self::ACCESS_DENIED;
        if ($group === $this->groupProvider->getGroup()) {
            $result = self::ACCESS_ABSTAIN;

            $permissions = $this->extension->getPermissions(null, false, true);
            foreach ($attributes as $attribute) {
                if (!$this->supportsAttribute($attribute)) {
                    continue;
                }

                if (!in_array($attribute, $permissions, true)) {
                    $result = self::ACCESS_DENIED;
                    break;
                }
            }
        }

        return $result;
    }
}
