<?php

namespace Oro\Bundle\SecurityBundle\Acl\Group;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Chain ACL group provider. Selects group from first supporting provider in chain.
 */
class ChainAclGroupProvider implements AclGroupProviderInterface
{
    /**
     * @var ArrayCollection|AclGroupProviderInterface[]
     */
    protected $providers;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    /**
     * Adds all providers that marked by tag: oro_security.acl.group_provider
     *
     * @param string $alias
     * @param AclGroupProviderInterface $provider
     */
    public function addProvider($alias, AclGroupProviderInterface $provider)
    {
        $this->providers->set($alias, $provider);
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup()
    {
        $provider = $this->getSupportedProvider();

        return $provider ? $provider->getGroup() : self::DEFAULT_SECURITY_GROUP;
    }

    /**
     * @return AclGroupProviderInterface|null
     */
    protected function getSupportedProvider()
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return $provider;
            }
        }

        return null;
    }
}
