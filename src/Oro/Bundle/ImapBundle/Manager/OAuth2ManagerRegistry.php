<?php

namespace Oro\Bundle\ImapBundle\Manager;

/**
 * Aggregates all available instances of Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface
 */
class OAuth2ManagerRegistry
{
    /** @var Oauth2ManagerInterface[] */
    private $managers = [];

    /**
     * Adds new manager
     *
     * @param Oauth2ManagerInterface $manager
     * @return self
     */
    public function addManager(Oauth2ManagerInterface $manager): self
    {
        if (array_key_exists($type = $manager->getType(), $this->managers)) {
            throw new \InvalidArgumentException(sprintf('Manager for type %s already exists', $type));
        }
        $this->managers[$type] = $manager;

        return $this;
    }

    /**
     * Returns all registered managers types
     *
     * @return array|string[]
     */
    public function getTypes(): array
    {
        return array_keys($this->managers);
    }

    /**
     * Returns all registered managers
     *
     * @return array|Oauth2ManagerInterface[]
     */
    public function getManagers(): array
    {
        return array_values($this->managers);
    }

    /**
     * @param string $type
     * @return Oauth2ManagerInterface
     */
    public function getManager(string $type): Oauth2ManagerInterface
    {
        if (!array_key_exists($type, $this->managers)) {
            throw new \InvalidArgumentException(sprintf('Manager for type %s does not exists', $type));
        }

        return $this->managers[$type];
    }

    /**
     * Returns true if registry contains certain manager
     *
     * @param string $type
     * @return bool
     */
    public function hasManager(string $type): bool
    {
        return array_key_exists($type, $this->managers);
    }

    /**
     * Returns true if any registered manager is available or
     * manager available for given type
     *
     * @param string|null $type
     * @return bool
     */
    public function isOauthImapEnabled(string $type = null): bool
    {
        foreach ($this->managers as $manager) {
            if (!$type && $manager->isOAuthEnabled()) {
                return true;
            }

            if ($type && ($manager->getType() === $type) && $manager->isOAuthEnabled()) {
                return true;
            }
        }

        return false;
    }
}
