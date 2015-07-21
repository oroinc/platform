<?php

namespace Oro\Bundle\EntityBundle\Provider;

class EntityNameResolver
{
    /** @var string */
    protected $defaultFormat;

    /** @var array */
    private $config;

    /** @var array */
    private $normalizedConfig;

    /** @var array */
    private $providers;

    /** @var EntityNameProviderInterface[] */
    private $sorted;

    /**
     * @param string $defaultFormat The default representation format
     * @param array  $config        The configuration of representation formats
     *
     * @throws \InvalidArgumentException if default format is not specified or does not exist
     */
    public function __construct($defaultFormat, array $config)
    {
        if (empty($defaultFormat)) {
            throw new \InvalidArgumentException('The default representation format must be specified.');
        }
        if (!isset($config[$defaultFormat])) {
            throw new \InvalidArgumentException(
                sprintf('The unknown default representation format "%s".', $defaultFormat)
            );
        }

        $this->defaultFormat = $defaultFormat;
        $this->config        = $config;
    }

    /**
     * Registers the provider in the chain.
     *
     * @param EntityNameProviderInterface $provider
     * @param int                         $priority
     */
    public function addProvider(EntityNameProviderInterface $provider, $priority = 0)
    {
        $this->providers[$priority][] = $provider;
        $this->sorted                 = null;
    }

    /**
     * Returns a text representation of the given entity.
     *
     * @param object      $entity The entity object
     * @param string|null $format The representation format, for example full, short, etc.
     *                            If not specified a default representation is used
     * @param string|null $locale The representation locale.
     *                            If not specified a default locale is used
     *
     * @return string A text representation of an entity or NULL if the name cannot be resolved
     */
    public function getName($entity, $format = null, $locale = null)
    {
        if (null === $entity) {
            return null;
        }

        $result    = null;
        $formats   = $this->getFormatConfig($format ?: $this->defaultFormat);
        $providers = $this->getProviders();
        foreach ($formats as $currentFormat) {
            foreach ($providers as $provider) {
                $val = $provider->getName($currentFormat['name'], $locale, $entity);
                if (false !== $val) {
                    $result = $val;
                    break 2;
                }
            }
        }

        return $result;
    }

    /**
     * Returns a DQL expression that can be used to get a text representation of the given type of entities.
     *
     * @param string      $className The FQCN of the entity
     * @param string      $alias     The alias in SELECT or JOIN statement
     * @param string|null $format    The representation format, for example full, short, etc.
     *                               If not specified a default representation is used
     * @param string|null $locale    The representation locale.
     *                               If not specified a default locale is used
     *
     * @return string A DQL expression or NULL if the name cannot be resolved
     */
    public function getNameDQL($className, $alias, $format = null, $locale = null)
    {
        $result = null;

        $formats   = $this->getFormatConfig($format ?: $this->defaultFormat);
        $providers = $this->getProviders();
        foreach ($formats as $currentFormat) {
            foreach ($providers as $provider) {
                $val = $provider->getNameDQL($currentFormat['name'], $locale, $className, $alias);
                if (false !== $val) {
                    $result = $val;
                    break 2;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the registered providers sorted by priority.
     *
     * @return EntityNameProviderInterface[]
     */
    protected function getProviders()
    {
        if (null === $this->sorted) {
            if (empty($this->providers)) {
                $this->sorted = [];
            } else {
                krsort($this->providers);
                $this->sorted = call_user_func_array('array_merge', $this->providers);
            }
        }

        return $this->sorted;
    }

    /**
     * Returns the configuration of the given format
     *
     * @param string $format
     *
     * @return array
     *
     * @throws \InvalidArgumentException if unknown format is passes
     */
    protected function getFormatConfig($format)
    {
        if (null === $this->normalizedConfig) {
            $this->normalizedConfig = $this->normalizeConfig($this->config);
        }
        if (!isset($this->normalizedConfig[$format])) {
            throw new \InvalidArgumentException(sprintf('The unknown representation format "%s".', $format));
        }

        return $this->normalizedConfig[$format];
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function normalizeConfig($config)
    {
        $result = [];
        $names  = array_keys($config);
        foreach ($names as $name) {
            $fallback = $name;
            while ($fallback) {
                $format          = $config[$fallback];
                $format['name']  = $fallback;
                $result[$name][] = $format;
                $fallback        = $format['fallback'];
            }
        }

        return $result;
    }
}
