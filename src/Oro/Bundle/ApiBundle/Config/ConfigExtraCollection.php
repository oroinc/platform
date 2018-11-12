<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * The collection of requests for configuration data.
 */
class ConfigExtraCollection
{
    /** @var ConfigExtraInterface[] */
    private $extras = [];

    /**
     * Indicates whether the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->extras);
    }

    /**
     * Gets a list of requests for configuration data.
     *
     * @return ConfigExtraInterface[]
     */
    public function getConfigExtras(): array
    {
        return $this->extras;
    }

    /**
     * Sets a list of requests for configuration data.
     *
     * @param ConfigExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setConfigExtras(array $extras): void
    {
        foreach ($extras as $extra) {
            if (!$extra instanceof ConfigExtraInterface) {
                throw new \InvalidArgumentException(\sprintf(
                    'Expected an array of "%s".',
                    ConfigExtraInterface::class
                ));
            }
        }
        $this->extras = \array_values($extras);
    }

    /**
     * Checks whether some configuration data is requested.
     *
     * @param string $extraName
     *
     * @return bool
     */
    public function hasConfigExtra(string $extraName): bool
    {
        foreach ($this->extras as $extra) {
            if ($extra->getName() === $extraName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a request for configuration data by its name.
     *
     * @param string $extraName
     *
     * @return ConfigExtraInterface|null
     */
    public function getConfigExtra(string $extraName): ?ConfigExtraInterface
    {
        foreach ($this->extras as $extra) {
            if ($extra->getName() === $extraName) {
                return $extra;
            }
        }

        return null;
    }

    /**
     * Adds a request for some configuration data.
     *
     * @param ConfigExtraInterface $extra
     *
     * @throws \InvalidArgumentException if a config extra with the same name already exists
     */
    public function addConfigExtra(ConfigExtraInterface $extra): void
    {
        if ($this->hasConfigExtra($extra->getName())) {
            throw new \InvalidArgumentException(\sprintf(
                'The "%s" config extra already exists.',
                $extra->getName()
            ));
        }
        $this->extras[] = $extra;
    }

    /**
     * Removes a request for some configuration data.
     *
     * @param string $extraName
     */
    public function removeConfigExtra(string $extraName): void
    {
        $keys = \array_keys($this->extras);
        foreach ($keys as $key) {
            if ($this->extras[$key]->getName() === $extraName) {
                unset($this->extras[$key]);
            }
        }
        $this->extras = \array_values($this->extras);
    }

    /**
     * Gets names of all requested configuration sections.
     *
     * @return string[]
     */
    public function getConfigSections(): array
    {
        $sections = [];
        foreach ($this->extras as $extra) {
            if ($extra instanceof ConfigExtraSectionInterface) {
                $sections[] = $extra->getName();
            }
        }

        return $sections;
    }

    /**
     * Checks whether some configuration data is requested.
     *
     * @param string $sectionName
     *
     * @return bool
     */
    public function hasConfigSection(string $sectionName): bool
    {
        $result = false;
        foreach ($this->extras as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface && $configExtra->getName() === $sectionName) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}
