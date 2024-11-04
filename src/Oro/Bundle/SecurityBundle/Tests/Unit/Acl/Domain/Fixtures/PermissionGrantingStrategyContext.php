<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures;

use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PermissionGrantingStrategyContext implements PermissionGrantingStrategyContextInterface
{
    /**
     * @var AclExtensionSelector
     */
    protected $extensionSelector;

    private $object = null;

    private $token = null;

    /**
     * @var OneShotIsGrantedObserver
     */
    protected $oneShotIsGrantedObserver;

    public function __construct(AclExtensionSelector $selector)
    {
        $this->extensionSelector = $selector;
    }

    #[\Override]
    public function getObject()
    {
        return $this->object;
    }

    public function setObject($object)
    {
        $this->object = $object;
    }

    #[\Override]
    public function getSecurityToken()
    {
        return $this->token;
    }

    public function setSecurityToken(TokenInterface $token)
    {
        $this->token = $token;
    }

    #[\Override]
    public function getAclExtension()
    {
        return $this->extensionSelector->select($this->object);
    }

    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer)
    {
        $this->oneShotIsGrantedObserver = $observer;
    }

    #[\Override]
    public function setTriggeredMask($mask, $accessLevel)
    {
    }
}
