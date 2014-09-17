<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\AclVoter as BaseAclVoter;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function setConfigProvider(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
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

        try {
            $this->extension = $this->extensionSelector->select($object);
        } catch (InvalidDomainObjectException $e) {
            return self::ACCESS_ABSTAIN;
        }

        // replace empty permissions with default ones
        for ($i = 0; $i < count($attributes); $i++) {
            if (empty($attributes[$i])) {
                $attributes[$i] = $this->extension->getDefaultPermission();
            }
        }

        $result = parent::vote($token, $object, $attributes);

        //check organization context
        $result = $this->checkOrganizationContext($result);

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
            $className = ClassUtils::getClass($object);
            if ($this->configProvider->hasConfig($className)) {
                $config = $this->configProvider->getConfig($className);
                $accessLevel = $this->extension->getAccessLevel($this->triggeredMask);
                if (($accessLevel < AccessLevel::SYSTEM_LEVEL)
                    || ($accessLevel === AccessLevel::SYSTEM_LEVEL
                        && in_array($config->get('owner_type'), ['USER', 'BUSINESS_UNIT']))
                ) {
                    if ($config->has('organization_field_name')) {
                        $accessor = PropertyAccess::createPropertyAccessor();
                        /** @var Organization $objectOrganization */
                        $objectOrganization = $accessor->getValue($object, $config->get('organization_field_name'));
                        if ($objectOrganization
                            && $objectOrganization->getId() !== $token->getOrganizationContext()->getId()
                        ) {
                            $result = self::ACCESS_DENIED;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
