<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * User config scope
 */
class UserScopeManager extends AbstractScopeManager implements ContainerAwareInterface
{
    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var int
     */
    protected $scopeId;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->security = $this->getSecurity();
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingValue($name, $full = false)
    {
        if (is_null($this->scopeId) || $this->scopeId == 0) {
            $this->setScopeId();
        }

        return parent::getSettingValue($name, $full);
    }

    /**
     * @param int|null $scopeId
     * @return $this
     */
    public function setScopeId($scopeId = null)
    {
        if (is_null($scopeId)) {
            $token = $this->getSecurity()->getToken();
            if ($token) {
                $user = $token->getUser();
                if (is_object($user)) {
                    $scopeId = $user->getId() ?: 0;
                }
            }
        }

        $this->scopeId = $scopeId;
        $this->loadStoredSettings($this->getScopedEntityName(), $this->scopeId);

        return $this;
    }

    /**
     * DI setter for security context
     *
     * @param SecurityContextInterface $security
     *
     * @deprecated since 1.8
     */
    public function setSecurity(SecurityContextInterface $security)
    {
        $this->security = $security;

        $this->loadUserStoredSettings($this->security->getToken());
    }

    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        if (is_null($this->scopeId)) {
            $this->setScopeId();
        }

        return $this->scopeId;
    }

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurity()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface is not injected');
        }

        if (!$this->security) {
            $this->security = $this->container->get('security.context');
        }

        $this->loadUserStoredSettings($this->security->getToken());

        return $this->security;
    }

    /**
     * If we have a user - try to merge his scoped settings into global settings array
     *
     * @param TokenInterface|null $token
     */
    protected function loadUserStoredSettings(TokenInterface $token = null)
    {
        if (!$token) {
            return;
        }

        /** @var User $user */
        $user = $token->getUser();
        if (is_object($user)) {
            foreach ($user->getGroups() as $group) {
                $this->loadStoredSettings('group', $group->getId());
            }

            $this->loadStoredSettings('user', $user->getId());
        }
    }
}
