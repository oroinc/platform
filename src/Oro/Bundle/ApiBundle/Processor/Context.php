<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Bundle\ApiBundle\Collection\CaseInsensitiveParameterBag;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\NullFilterValueAccessor;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Context extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** a prefix for all configuration sections */
    const CONFIG_PREFIX = 'config_';

    /** a list of requests for configuration data */
    const CONFIG_EXTRAS = 'configExtras';

    /** metadata of an entity */
    const METADATA = 'metadata';

    /** a list of requests for additional metadata info */
    const METADATA_EXTRAS = 'metadataExtras';

    /** a query is used to get result data */
    const QUERY = 'query';

    /** the Criteria object is used to add additional restrictions to a query is used to get result data */
    const CRITERIA = 'criteria';

    /** the response status code */
    const RESPONSE_STATUS_CODE = 'responseStatusCode';

    /**
     * this header can be used to request additional data like "total count"
     * that will be returned in a response headers
     */
    const INCLUDE_HEADER = 'X-Include';

    /** a list of filters is used to add additional restrictions to a query is used to get result data */
    const FILTERS = 'filters';

    /** @var FilterValueAccessorInterface */
    private $filterValues;

    /** @var Error[] */
    private $errors;

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
        parent::__construct();
        $this->configProvider   = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * Checks whether a configuration of filters for an entity exists.
     *
     * @return bool
     */
    public function hasConfigOfFilters()
    {
        return $this->hasConfigOf(FiltersConfigExtra::NAME);
    }

    /**
     * Gets a configuration of filters for an entity.
     *
     * @return FiltersConfig|null
     */
    public function getConfigOfFilters()
    {
        return $this->getConfigOf(FiltersConfigExtra::NAME);
    }

    /**
     * Sets a configuration of filters for an entity.
     *
     * @param FiltersConfig|null $config
     */
    public function setConfigOfFilters(FiltersConfig $config = null)
    {
        $this->setConfigOf(FiltersConfigExtra::NAME, $config);
    }

    /**
     * Checks whether a configuration of sorters for an entity exists.
     *
     * @return bool
     */
    public function hasConfigOfSorters()
    {
        return $this->hasConfigOf(SortersConfigExtra::NAME);
    }

    /**
     * Gets a configuration of sorters for an entity.
     *
     * @return SortersConfig|null
     */
    public function getConfigOfSorters()
    {
        return $this->getConfigOf(SortersConfigExtra::NAME);
    }

    /**
     * Sets a configuration of sorters for an entity.
     *
     * @param SortersConfig|null $config
     */
    public function setConfigOfSorters(SortersConfig $config = null)
    {
        $this->setConfigOf(SortersConfigExtra::NAME, $config);
    }

    /**
     * Gets a list of filters is used to add additional restrictions to a query is used to get result data.
     *
     * @return FilterCollection
     */
    public function getFilters()
    {
        if (!$this->has(self::FILTERS)) {
            $this->set(self::FILTERS, new FilterCollection());
        }

        return $this->get(self::FILTERS);
    }

    /**
     * Gets a collection of the FilterValue objects that contains all incoming filters.
     *
     * @return FilterValueAccessorInterface
     */
    public function getFilterValues()
    {
        if (null === $this->filterValues) {
            $this->filterValues = new NullFilterValueAccessor();
        }

        return $this->filterValues;
    }

    /**
     * Sets an object that will be used to accessing incoming filters.
     *
     * @param FilterValueAccessorInterface $accessor
     */
    public function setFilterValues(FilterValueAccessorInterface $accessor)
    {
        $this->filterValues = $accessor;
    }

    /**
     * Gets a key of a main section of an entity configuration.
     *
     * @return string
     */
    protected function getConfigKey()
    {
        return self::CONFIG_PREFIX . ConfigUtil::DEFINITION;
    }

    /**
     * Loads an entity configuration.
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
            $this->getConfigExtras()
        );

        // add loaded config sections to the context
        if (!$config->isEmpty()) {
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
     * Makes sure that all config sections are added to the context.
     */
    protected function ensureAllConfigSectionsSet()
    {
        $configExtras = $this->getConfigExtras();
        foreach ($configExtras as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface) {
                $key = self::CONFIG_PREFIX . $configExtra->getName();
                if (!$this->has($key)) {
                    $this->set($key, null);
                }
            }
        }
    }

    /**
     * Loads an entity metadata.
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
     * Gets request headers.
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
     * Sets an object that will be used to accessing request headers.
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setRequestHeaders(ParameterBagInterface $parameterBag)
    {
        $this->requestHeaders = $parameterBag;
    }

    /**
     * Gets response headers.
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
     * Sets an object that will be used to accessing response headers.
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setResponseHeaders(ParameterBagInterface $parameterBag)
    {
        $this->responseHeaders = $parameterBag;
    }

    /**
     * Gets the response status code.
     *
     * @return int|null
     */
    public function getResponseStatusCode()
    {
        return $this->get(self::RESPONSE_STATUS_CODE);
    }

    /**
     * Sets the response status code.
     *
     * @param $statusCode
     */
    public function setResponseStatusCode($statusCode)
    {
        $this->set(self::RESPONSE_STATUS_CODE, $statusCode);
    }

    /**
     * Gets FQCN of an entity.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity.
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Checks whether a configuration of an entity exists.
     *
     * @return bool
     */
    public function hasConfig()
    {
        return $this->has($this->getConfigKey());
    }

    /**
     * Gets a configuration of an entity.
     *
     * @return EntityDefinitionConfig|null
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
     * Sets a configuration of an entity.
     *
     * @param EntityDefinitionConfig|null $definition
     */
    public function setConfig(EntityDefinitionConfig $definition = null)
    {
        $this->set($this->getConfigKey(), $definition);

        // make sure that all config sections are added to the context
        $this->ensureAllConfigSectionsSet();
    }

    /**
     * Checks whether a configuration of the given section exists.
     *
     * @param string $configSection
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function hasConfigOf($configSection)
    {
        $this->assertConfigSection($configSection);

        return $this->has(self::CONFIG_PREFIX . $configSection);
    }

    /**
     * Gets a configuration from the given section.
     *
     * @param string $configSection
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function getConfigOf($configSection)
    {
        $this->assertConfigSection($configSection);

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
     * Sets a configuration for the given section.
     *
     * @param string $configSection
     * @param mixed  $config
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function setConfigOf($configSection, $config)
    {
        $this->assertConfigSection($configSection);

        $this->set(self::CONFIG_PREFIX . $configSection, $config);

        // make sure that all config sections, including a main section, are added to the context
        $key = $this->getConfigKey();
        if (!$this->has($key)) {
            $this->set($key, null);
        }
        $this->ensureAllConfigSectionsSet();
    }

    /**
     * Gets a list of requests for configuration data.
     *
     * @return ConfigExtraInterface[]
     */
    public function getConfigExtras()
    {
        $extras = $this->get(self::CONFIG_EXTRAS);

        return null !== $extras
            ? $extras
            : [];
    }

    /**
     * Sets a list of requests for configuration data.
     *
     * @param ConfigExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setConfigExtras(array $extras)
    {
        foreach ($extras as $configExtra) {
            if (!$configExtra instanceof ConfigExtraInterface) {
                throw new \InvalidArgumentException(
                    'Expected an array of "Oro\Bundle\ApiBundle\Config\ConfigExtraInterface".'
                );
            }
        }

        if (empty($extras)) {
            $this->remove(self::CONFIG_EXTRAS);
        } else {
            $this->set(self::CONFIG_EXTRAS, $extras);
        }
    }

    /**
     * Checks whether some configuration data is requested.
     *
     * @param string $extraName
     *
     * @return bool
     */
    public function hasConfigExtra($extraName)
    {
        $configExtras = $this->getConfigExtras();
        foreach ($configExtras as $configExtra) {
            if ($configExtra->getName() === $extraName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds a request for some configuration data.
     *
     * @param ConfigExtraInterface $extra
     *
     * @throws \InvalidArgumentException if a config extra with the same name already exists
     */
    public function addConfigExtra(ConfigExtraInterface $extra)
    {
        if ($this->hasConfigExtra($extra->getName())) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" config extra already exists.', $extra->getName())
            );
        }
        $extras   = $this->getConfigExtras();
        $extras[] = $extra;
        $this->setConfigExtras($extras);
    }

    /**
     * Removes a request for some configuration data.
     *
     * @param string $extraName
     */
    public function removeConfigExtra($extraName)
    {
        $configExtras = $this->getConfigExtras();
        $keys         = array_keys($configExtras);
        foreach ($keys as $key) {
            if ($configExtras[$key]->getName() === $extraName) {
                unset($configExtras[$key]);
            }
        }
        $this->setConfigExtras(array_values($configExtras));
    }

    /**
     * Checks whether metadata of an entity exists.
     *
     * @return bool
     */
    public function hasMetadata()
    {
        return $this->has(self::METADATA);
    }

    /**
     * Gets metadata of an entity.
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
     * Sets metadata of an entity.
     *
     * @param EntityMetadata|null $metadata
     */
    public function setMetadata(EntityMetadata $metadata = null)
    {
        $this->set(self::METADATA, $metadata);
    }

    /**
     * Gets a list of requests for additional metadata info.
     *
     * @return MetadataExtraInterface[]
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
     * @param MetadataExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setMetadataExtras(array $extras)
    {
        foreach ($extras as $configExtra) {
            if (!$configExtra instanceof MetadataExtraInterface) {
                throw new \InvalidArgumentException(
                    'Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".'
                );
            }
        }

        if (empty($extras)) {
            $this->remove(self::METADATA_EXTRAS);
        } else {
            $this->set(self::METADATA_EXTRAS, $extras);
        }
    }

    /**
     * Checks whether some additional metadata info is requested.
     *
     * @param string $extraName
     *
     * @return bool
     */
    public function hasMetadataExtra($extraName)
    {
        $metadataExtras = $this->getMetadataExtras();
        foreach ($metadataExtras as $metadataExtra) {
            if ($metadataExtra->getName() === $extraName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds a request for some additional metadata info.
     *
     * @param MetadataExtraInterface $extra
     *
     * @throws \InvalidArgumentException if a metadata extra with the same name already exists
     */
    public function addMetadataExtra(MetadataExtraInterface $extra)
    {
        if ($this->hasMetadataExtra($extra->getName())) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" metadata extra already exists.', $extra->getName())
            );
        }
        $extras   = $this->getMetadataExtras();
        $extras[] = $extra;
        $this->setMetadataExtras($extras);
    }

    /**
     * Removes a request for some additional metadata info.
     *
     * @param string $extraName
     */
    public function removeMetadataExtra($extraName)
    {
        $metadataExtras = $this->getMetadataExtras();
        $keys           = array_keys($metadataExtras);
        foreach ($keys as $key) {
            if ($metadataExtras[$key]->getName() === $extraName) {
                unset($metadataExtras[$key]);
            }
        }
        $this->setMetadataExtras(array_values($metadataExtras));
    }

    /**
     * Checks whether a query is used to get result data exists.
     *
     * @return bool
     */
    public function hasQuery()
    {
        return $this->has(self::QUERY);
    }

    /**
     * Gets a query is used to get result data.
     *
     * @return mixed
     */
    public function getQuery()
    {
        return $this->get(self::QUERY);
    }

    /**
     * Sets a query is used to get result data.
     *
     * @param mixed $query
     */
    public function setQuery($query)
    {
        $this->set(self::QUERY, $query);
    }

    /**
     * Gets the Criteria object is used to add additional restrictions to a query is used to get result data.
     *
     * @return Criteria
     */
    public function getCriteria()
    {
        return $this->get(self::CRITERIA);
    }

    /**
     * Sets the Criteria object is used to add additional restrictions to a query is used to get result data.
     *
     * @param Criteria $criteria
     */
    public function setCriteria($criteria)
    {
        $this->set(self::CRITERIA, $criteria);
    }

    /**
     * Whether any error happened during the processing of an action.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Gets all errors happened during the processing of an action.
     *
     * @return Error[]
     */
    public function getErrors()
    {
        return null !== $this->errors
            ? $this->errors
            : [];
    }

    /**
     * Registers an error.
     *
     * @param Error $error
     */
    public function addError(Error $error)
    {
        if (null === $this->errors) {
            $this->errors = [];
        }
        $this->errors[] = $error;
    }

    /**
     * Removes all errors.
     */
    public function resetErrors()
    {
        $this->errors = null;
    }

    /**
     * @param string $configSection
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    protected function assertConfigSection($configSection)
    {
        $valid        = false;
        $configExtras = $this->getConfigExtras();
        foreach ($configExtras as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface && $configSection === $configExtra->getName()) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            throw new \InvalidArgumentException(sprintf('Undefined configuration section: "%s".', $configSection));
        }
    }
}
