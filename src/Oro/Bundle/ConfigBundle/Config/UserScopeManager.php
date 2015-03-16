<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * User config scope
 */
class UserScopeManager extends AbstractScopeManager
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
            if ($token = $this->security->getToken()) {
                if (is_object($user = $token->getUser())) {
                    $scopeId = $user->getId() ? : 0;
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
     */
    public function setSecurity(SecurityContextInterface $security)
    {
        $this->security = $security;

        // if we have a user - try to merge his scoped settings into global settings array
        if ($token = $this->security->getToken()) {
            if (is_object($user = $token->getUser())) {
                foreach ($user->getGroups() as $group) {
                    $this->loadStoredSettings('group', $group->getId());
                }

                $this->loadStoredSettings('user', $user->getId());
            }
        }
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
}
