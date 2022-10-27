<?php

namespace Oro\Bundle\SecurityBundle\Acl\Group;

/**
 * Chain ACL group provider. Selects group from first supporting provider in chain.
 */
class ChainAclGroupProvider implements AclGroupProviderInterface
{
    /** @var AclGroupProviderInterface[] */
    private $providers;

    /**
     * @param AclGroupProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
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
    private function getSupportedProvider()
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports()) {
                return $provider;
            }
        }

        return null;
    }
}
