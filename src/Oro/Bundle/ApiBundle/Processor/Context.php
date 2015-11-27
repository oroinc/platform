<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Bundle\ApiBundle\Collection\CaseInsensitiveParameterBag;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Context extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** a prefix for all configuration sections */
    const CONFIG_PREFIX = 'config_';

    /** a list of required additional configuration sections, for example "filters", "sorters", etc. */
    const CONFIG_SECTIONS = 'configSections';

    /** a list of requests for additional configuration data, for example "descriptions" */
    const CONFIG_EXTRAS = 'configExtras';

    /** metadata of an entity */
    const METADATA = 'metadata';

    /** a list of requests for additional metadata info */
    const METADATA_EXTRAS = 'metadataExtras';

    /** a query is used to get result data */
    const QUERY = 'query';

    /** the Criteria object is used to add additional restrictions to a query is used to get result data */
    const CRITERIA = 'criteria';

    /**
     * this header can be used to request additional data like "total count"
     * that will be returned in a response headers
     */
    const INCLUDE_HEADER = 'X-Include';

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /** @var ParameterBagInterface */
    private $requestHeaders;

    /** @var ParameterBagInterface */
    private $responseHeaders;

    /**
     * @param ConfigProvider   $configProvider
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        $this->configProvider   = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * Gets a key of a main section of an entity configuration
     *
     * @return string
     */
    protected function getConfigKey()
    {
        return self::CONFIG_PREFIX . ConfigUtil::DEFINITION;
    }

    /**
     * Loads an entity configuration
     */
    protected function loadConfig()
    {
        $entityClass = $this->getClassName();
        if (empty($entityClass)) {
            throw new \RuntimeException(
                'A class name must be set in the context before a configuration is loaded.'
            );
        }

        // load config by a config provider
        $config = $this->configProvider->getConfig(
            $entityClass,
            $this->getVersion(),
            $this->getRequestType(),
            array_unique(array_merge($this->getConfigSections(), $this->getConfigExtras()))
        );

        // add loaded config sections to the context
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->set(self::CONFIG_PREFIX . $key, $value);
            }
        }

        // make sure that all config sections, including a main section, are added to the context
        // even if a section was not returned by the config provider
        $key = $this->getConfigKey();
        if (!$this->has($key)) {
            $this->set($key, null);
        }
        $this->ensureAllConfigSectionsSet();
    }

    /**
     * Makes sure that all config sections are added to the context
     */
    protected function ensureAllConfigSectionsSet()
    {
        $configSections = $this->getConfigSections();
        foreach ($configSections as $section) {
            $key = self::CONFIG_PREFIX . $section;
            if (!$this->has($key)) {
                $this->set($key, null);
            }
        }
    }

    /**
     * Loads an entity metadata
     */
    protected function loadMetadata()
    {
        $entityClass = $this->getClassName();
        if (empty($entityClass)) {
            throw new \RuntimeException(
                'A class name must be set in the context before metadata are loaded.'
            );
        }

        // load metadata by a metadata provider
        $metadata = $this->metadataProvider->getMetadata(
            $entityClass,
            $this->getVersion(),
            $this->getRequestType(),
            $this->getMetadataExtras(),
            $this->getConfig()
        );

        // add loaded metadata to the context
        $this->set(self::METADATA, $metadata);
    }

    /**
     * Gets headers an API request
     *
     * @return ParameterBagInterface
     */
    public function getRequestHeaders()
    {
        if (null === $this->requestHeaders) {
            $this->requestHeaders = new CaseInsensitiveParameterBag();
        }

        return $this->requestHeaders;
    }

    /**
     * Sets an object that will be used to accessing headers an API request
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setRequestHeaders(ParameterBagInterface $parameterBag)
    {
        $this->requestHeaders = $parameterBag;
    }

    /**
     * Gets headers an API response
     *
     * @return ParameterBagInterface
     */
    public function getResponseHeaders()
    {
        if (null === $this->responseHeaders) {
            $this->responseHeaders = new ParameterBag();
        }

        return $this->responseHeaders;
    }

    /**
     * Sets an object that will be used to accessing headers an API response
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setResponseHeaders(ParameterBagInterface $parameterBag)
    {
        $this->responseHeaders = $parameterBag;
    }

    /**
     * Gets FQCN of an entity
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Checks whether a configuration of an entity exists
     *
     * @return bool
     */
    public function hasConfig()
    {
        return $this->has($this->getConfigKey());
    }

    /**
     * Gets a configuration of an entity
     *
     * @return array|null
     */
    public function getConfig()
    {
        $key = $this->getConfigKey();
        if (!$this->has($key)) {
            $this->loadConfig();
        }

        return $this->get($key);
    }

    /**
     * Sets a configuration of an entity
     *
     * @param array|null $config
     */
    public function setConfig($config)
    {
        $this->set($this->getConfigKey(), $config);

        // make sure that all config sections are added to the context
        $this->ensureAllConfigSectionsSet();
    }

    /**
     * Checks whether a configuration of the given section exists
     *
     * @param string $configSection
     *
     * @return bool
     */
    public function hasConfigOf($configSection)
    {
        if (!in_array($configSection, $this->getConfigSections(), true)) {
            throw new \InvalidArgumentException(sprintf('Undefined configuration section: "%s".', $configSection));
        }

        return $this->has(self::CONFIG_PREFIX . $configSection);
    }

    /**
     * Gets a configuration from the given section
     *
     * @param string $configSection
     *
     * @return array|null
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function getConfigOf($configSection)
    {
        if (!in_array($configSection, $this->getConfigSections(), true)) {
            throw new \InvalidArgumentException(sprintf('Undefined configuration section: "%s".', $configSection));
        }

        $key = self::CONFIG_PREFIX . $configSection;
        if (!$this->has($key)) {
            if (!$this->has($this->getConfigKey())) {
                $this->loadConfig();
            } else {
                $this->setConfigOf($configSection, null);
            }
        }

        return $this->get($key);
    }

    /**
     * Sets a configuration for the given section
     *
     * @param string     $configSection
     * @param array|null $config
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function setConfigOf($configSection, $config)
    {
        if (!in_array($configSection, $this->getConfigSections(), true)) {
            throw new \InvalidArgumentException(sprintf('Undefined configuration section: "%s".', $configSection));
        }

        $this->set(self::CONFIG_PREFIX . $configSection, $config);

        // make sure that all config sections, including a main section, are added to the context
        $key = $this->getConfigKey();
        if (!$this->has($key)) {
            $this->set($key, null);
        }
        $this->ensureAllConfigSectionsSet();
    }

    /**
     * Gets a list of required additional configuration sections, for example "filters", "sorters", etc.
     *
     * @return string[]
     */
    public function getConfigSections()
    {
        $sections = $this->get(self::CONFIG_SECTIONS);

        return null !== $sections
            ? $sections
            : [];
    }

    /**
     * Sets a list of required additional configuration sections, for example "filters", "sorters", etc.
     *
     * @param string[] $sections
     */
    public function setConfigSections($sections)
    {
        if (empty($sections)) {
            $this->remove(self::CONFIG_SECTIONS, $sections);
        } else {
            $this->set(self::CONFIG_SECTIONS, $sections);
        }
    }

    /**
     * Checks whether a section exists in a list of required additional configuration sections.
     *
     * @param string $section
     *
     * @return bool
     */
    public function hasConfigSection($section)
    {
        return in_array($section, $this->getConfigSections(), true);
    }

    /**
     * Adds a section to a list of required additional configuration sections.
     *
     * @param string $section
     */
    public function addConfigSection($section)
    {
        $sections = $this->getConfigSections();
        if (!in_array($section, $sections, true)) {
            $sections[] = $section;
            $this->setConfigSections($sections);
        }
    }

    /**
     * Removes a section from a list of required additional configuration sections.
     *
     * @param string $section
     */
    public function removeConfigSection($section)
    {
        $sections = $this->getConfigSections();
        if (in_array($section, $sections, true)) {
            $this->setConfigSections(array_values(array_diff($sections, [$section])));
        }
    }

    /**
     * Gets a list of requests for additional configuration data, for example "descriptions".
     *
     * @return string[]
     */
    public function getConfigExtras()
    {
        $extras = $this->get(self::CONFIG_EXTRAS);

        return null !== $extras
            ? $extras
            : [];
    }

    /**
     * Sets a list of requests for additional configuration data, for example "descriptions".
     *
     * @param string[] $extras
     */
    public function setConfigExtras($extras)
    {
        if (empty($extras)) {
            $this->remove(self::CONFIG_EXTRAS, $extras);
        } else {
            $this->set(self::CONFIG_EXTRAS, $extras);
        }
    }

    /**
     * Checks whether some additional configuration data is requested.
     *
     * @param string $extra
     *
     * @return bool
     */
    public function hasConfigExtra($extra)
    {
        return in_array($extra, $this->getConfigExtras(), true);
    }

    /**
     * Adds a request for some additional configuration data.
     *
     * @param string $extra
     */
    public function addConfigExtra($extra)
    {
        $extras = $this->getConfigExtras();
        if (!in_array($extra, $extras, true)) {
            $extras[] = $extra;
            $this->setConfigExtras($extras);
        }
    }

    /**
     * Removes a request for some additional configuration data.
     *
     * @param string $extra
     */
    public function removeConfigExtra($extra)
    {
        $extras = $this->getConfigExtras();
        if (in_array($extra, $extras, true)) {
            $this->setConfigExtras(array_values(array_diff($extras, [$extra])));
        }
    }

    /**
     * Checks whether metadata of an entity exists
     *
     * @return bool
     */
    public function hasMetadata()
    {
        return $this->has(self::METADATA);
    }

    /**
     * Gets metadata of an entity
     *
     * @return EntityMetadata|null
     */
    public function getMetadata()
    {
        if (!$this->has(self::METADATA)) {
            $this->loadMetadata();
        }

        return $this->get(self::METADATA);
    }

    /**
     * Sets metadata of an entity
     *
     * @param EntityMetadata|null $config
     */
    public function setMetadata($config)
    {
        $this->set(self::METADATA, $config);
    }

    /**
     * Gets a list of requests for additional metadata info.
     *
     * @return string[]
     */
    public function getMetadataExtras()
    {
        $extras = $this->get(self::METADATA_EXTRAS);

        return null !== $extras
            ? $extras
            : [];
    }

    /**
     * Sets a list of requests for additional metadata info.
     *
     * @param string[] $extras
     */
    public function setMetadataExtras($extras)
    {
        if (empty($extras)) {
            $this->remove(self::METADATA_EXTRAS, $extras);
        } else {
            $this->set(self::METADATA_EXTRAS, $extras);
        }
    }

    /**
     * Checks whether some additional metadata info is requested.
     *
     * @param string $extra
     *
     * @return bool
     */
    public function hasMetadataExtra($extra)
    {
        return in_array($extra, $this->getMetadataExtras(), true);
    }

    /**
     * Adds a request for some additional metadata info.
     *
     * @param string $extra
     */
    public function addMetadataExtra($extra)
    {
        $extras = $this->getMetadataExtras();
        if (!in_array($extra, $extras, true)) {
            $extras[] = $extra;
            $this->setMetadataExtras($extras);
        }
    }

    /**
     * Removes a request for some additional metadata info.
     *
     * @param string $extra
     */
    public function removeMetadataExtra($extra)
    {
        $extras = $this->getMetadataExtras();
        if (in_array($extra, $extras, true)) {
            $this->setMetadataExtras(array_values(array_diff($extras, [$extra])));
        }
    }

    /**
     * Checks whether a query is used to get result data exists
     *
     * @return bool
     */
    public function hasQuery()
    {
        return $this->has(self::QUERY);
    }

    /**
     * Gets a query is used to get result data
     *
     * @return mixed
     */
    public function getQuery()
    {
        return $this->get(self::QUERY);
    }

    /**
     * Sets a query is used to get result data
     *
     * @param mixed $query
     */
    public function setQuery($query)
    {
        $this->set(self::QUERY, $query);
    }

    /**
     * Gets the Criteria object is used to add additional restrictions to a query is used to get result data
     *
     * @return Criteria
     */
    public function getCriteria()
    {
        return $this->get(self::CRITERIA);
    }

    /**
     * Sets the Criteria object is used to add additional restrictions to a query is used to get result data
     *
     * @param Criteria $criteria
     */
    public function setCriteria($criteria)
    {
        $this->set(self::CRITERIA, $criteria);
    }
}
