<?php

namespace Oro\Bundle\ImapBundle\Manager;

/**
 * Aggregates all available instances of OAuth managers.
 */
class OAuthManagerRegistry
{
    /** @var OAuthManagerInterface[] [type => manager, ...] */
    private $managers = [];

    /**
     * @param iterable|OAuthManagerInterface[] $managers
     */
    public function __construct(iterable $managers)
    {
        foreach ($managers as $manager) {
            $type = $manager->getType();
            if (isset($this->managers[$type])) {
                throw new \InvalidArgumentException(sprintf('The manager for "%s" already exists.', $type));
            }
            $this->managers[$type] = $manager;
        }
    }

    /**
     * Gets types of all registered managers.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return array_keys($this->managers);
    }

    /**
     * Gets all registered managers.
     *
     * @return OAuthManagerInterface[]
     */
    public function getManagers(): array
    {
        return array_values($this->managers);
    }

    /**
     * Gets a manager by its type.
     */
    public function getManager(string $type): OAuthManagerInterface
    {
        if (!isset($this->managers[$type])) {
            throw new \InvalidArgumentException(sprintf('The manager for "%s" does not exist.', $type));
        }

        return $this->managers[$type];
    }

    /**
     * Checks if the registry contains a manager for the given type.
     */
    public function hasManager(string $type): bool
    {
        return isset($this->managers[$type]);
    }

    /**
     * Checks if any registered manager is enabled or a manager for the given type is enabled.
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
