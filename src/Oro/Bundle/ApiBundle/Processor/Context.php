<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Collection\CaseInsensitiveParameterBag;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraCollection;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\NullFilterValueAccessor;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraCollection;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * The base execution context for Data API processors for public actions.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Context extends NormalizeResultContext implements ContextInterface
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** a prefix for all configuration sections */
    const CONFIG_PREFIX = 'config_';

    /** metadata of an entity */
    const METADATA = 'metadata';

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

    /** indicates whether the current action processes a master API request */
    const MASTER_REQUEST = 'masterRequest';

    /** indicates whether the current request is CORS request */
    const CORS = 'cors';

    /** whether HATEOAS is enabled */
    const HATEOAS = 'hateoas';

    /** @var FilterCollection */
    private $filters;

    /** @var FilterValueAccessorInterface */
    private $filterValues;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /** @var ParameterBagInterface */
    private $requestHeaders;

    /** @var ParameterBagInterface */
    private $responseHeaders;

    /** @var DocumentBuilderInterface|null */
    private $responseDocumentBuilder;

    /** @var ConfigExtraCollection */
    private $configExtras;

    /** @var MetadataExtraCollection|null */
    private $metadataExtras;

    /** @var array|null */
    private $infoRecords;

    /**
     * @param ConfigProvider   $configProvider
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        parent::__construct();
        $this->configExtras = new ConfigExtraCollection();
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
        $this->set(self::MASTER_REQUEST, false);
        $this->set(self::CORS, false);
        $this->set(self::HATEOAS, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * {@inheritdoc}
     */
    public function hasIdentifierFields()
    {
        $metadata = $this->getMetadata();

        return null !== $metadata && $metadata->hasIdentifierFields();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestHeaders()
    {
        if (null === $this->requestHeaders) {
            $this->requestHeaders = new CaseInsensitiveParameterBag();
        }

        return $this->requestHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestHeaders(ParameterBagInterface $parameterBag)
    {
        $this->requestHeaders = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeaders()
    {
        if (null === $this->responseHeaders) {
            $this->responseHeaders = new ParameterBag();
        }

        return $this->responseHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponseHeaders(ParameterBagInterface $parameterBag)
    {
        $this->responseHeaders = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseStatusCode()
    {
        return $this->get(self::RESPONSE_STATUS_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setResponseStatusCode($statusCode)
    {
        $this->set(self::RESPONSE_STATUS_CODE, $statusCode);
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessResponse()
    {
        $statusCode = $this->getResponseStatusCode();

        return $statusCode>= 200 && $statusCode < 300;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseDocumentBuilder()
    {
        return $this->responseDocumentBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponseDocumentBuilder(?DocumentBuilderInterface $documentBuilder)
    {
        $this->responseDocumentBuilder = $documentBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        if (null === $this->filters) {
            $this->filters = new FilterCollection();
        }

        return $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterValues()
    {
        if (null === $this->filterValues) {
            $this->filterValues = new NullFilterValueAccessor();
        }

        return $this->filterValues;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilterValues(FilterValueAccessorInterface $accessor)
    {
        $this->filterValues = $accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function isMasterRequest(): bool
    {
        return $this->get(self::MASTER_REQUEST);
    }

    /**
     * {@inheritdoc}
     */
    public function setMasterRequest(bool $master): void
    {
        $this->set(self::MASTER_REQUEST, $master);
    }

    /**
     * {@inheritdoc}
     */
    public function isCorsRequest(): bool
    {
        return $this->get(self::CORS);
    }

    /**
     * {@inheritdoc}
     */
    public function setCorsRequest(bool $cors): void
    {
        $this->set(self::CORS, $cors);
    }

    /**
     * {@inheritdoc}
     */
    public function isHateoasEnabled(): bool
    {
        return (bool)$this->get(self::HATEOAS);
    }

    /**
     * {@inheritdoc}
     */
    public function setHateoas(bool $flag)
    {
        $this->set(self::HATEOAS, $flag);
    }

    /**
     * {@inheritdoc}
     */
    public function getInfoRecords(): ?array
    {
        return $this->infoRecords;
    }

    /**
     * {@inheritdoc}
     */
    public function setInfoRecords(?array $infoRecords): void
    {
        $this->infoRecords = $infoRecords;
    }

    /**
     * {@inheritdoc}
     */
    public function hasQuery()
    {
        return $this->has(self::QUERY);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->get(self::QUERY);
    }

    /**
     * {@inheritdoc}
     */
    public function setQuery($query)
    {
        if ($query) {
            $this->set(self::QUERY, $query);
        } else {
            $this->remove(self::QUERY);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteria()
    {
        return $this->get(self::CRITERIA);
    }

    /**
     * {@inheritdoc}
     */
    public function setCriteria($criteria)
    {
        $this->set(self::CRITERIA, $criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigExtras()
    {
        return $this->configExtras->getConfigExtras();
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigExtras(array $extras)
    {
        $this->configExtras->setConfigExtras($extras);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfigExtra($extraName)
    {
        return $this->configExtras->hasConfigExtra($extraName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigExtra($extraName)
    {
        return $this->configExtras->getConfigExtra($extraName);
    }

    /**
     * {@inheritdoc}
     */
    public function addConfigExtra(ConfigExtraInterface $extra)
    {
        $this->configExtras->addConfigExtra($extra);
    }

    /**
     * {@inheritdoc}
     */
    public function removeConfigExtra($extraName)
    {
        $this->configExtras->removeConfigExtra($extraName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSections()
    {
        return $this->configExtras->getConfigSections();
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfig()
    {
        return $this->has($this->getConfigKey());
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setConfig(?EntityDefinitionConfig $definition)
    {
        $this->set($this->getConfigKey(), $definition);
        if (null === $definition) {
            $this->removeAllConfigSections();
        }
        $this->ensureAllConfigSectionsSet();
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfigOfFilters()
    {
        return $this->hasConfigOf(FiltersConfigExtra::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigOfFilters()
    {
        return $this->getConfigOf(FiltersConfigExtra::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigOfFilters(?FiltersConfig $config)
    {
        $this->setConfigOf(FiltersConfigExtra::NAME, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfigOfSorters()
    {
        return $this->hasConfigOf(SortersConfigExtra::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigOfSorters()
    {
        return $this->getConfigOf(SortersConfigExtra::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigOfSorters(?SortersConfig $config)
    {
        $this->setConfigOf(SortersConfigExtra::NAME, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfigOf($configSection)
    {
        if (!$this->isKnownConfigSection($configSection)) {
            return false;
        }

        return $this->has(self::CONFIG_PREFIX . $configSection);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigOf($configSection)
    {
        if (!$this->isKnownConfigSection($configSection)) {
            return null;
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
     * {@inheritdoc}
     */
    public function setConfigOf($configSection, $config)
    {
        if (!$this->isKnownConfigSection($configSection)) {
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
     * Gets a key of a main section of an entity configuration.
     *
     * @return string
     */
    protected function getConfigKey()
    {
        return self::CONFIG_PREFIX . ConfigUtil::DEFINITION;
    }

    /**
     * @param string $entityClass
     * @param array  $extras
     *
     * @return Config
     */
    protected function loadEntityConfig($entityClass, array $extras)
    {
        $config = $this->configProvider->getConfig(
            $entityClass,
            $this->getVersion(),
            $this->getRequestType(),
            $extras
        );
        if ($this->isHateoasEnabled()) {
            $definition = $config->getDefinition();
            if (null !== $definition) {
                $definition->setHasMore(true);
            }
        }

        return $config;
    }

    /**
     * Loads an entity configuration.
     */
    protected function loadConfig()
    {
        $entityClass = $this->getClassName();
        if (empty($entityClass)) {
            $this->processLoadedConfig(null);

            throw new RuntimeException(
                'A class name must be set in the context before a configuration is loaded.'
            );
        }

        try {
            $config = $this->loadEntityConfig($entityClass, $this->getConfigExtras());
            $this->processLoadedConfig($config);
        } catch (\Exception $e) {
            $this->processLoadedConfig(null);

            throw $e;
        }
    }

    /**
     * @param Config|null $config
     */
    protected function processLoadedConfig(?Config $config)
    {
        // add loaded config sections to the context
        if ($config && !$config->isEmpty()) {
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
        $configSections = $this->getConfigSections();
        foreach ($configSections as $name) {
            $key = self::CONFIG_PREFIX . $name;
            if (!$this->has($key)) {
                $this->set($key, null);
            }
        }
    }

    /**
     * Removes all config sections from the context.
     */
    protected function removeAllConfigSections()
    {
        $configSections = $this->getConfigSections();
        foreach ($configSections as $name) {
            $key = self::CONFIG_PREFIX . $name;
            if ($this->has($key)) {
                $this->remove($key);
            }
        }
    }

    /**
     * @param string $configSection
     *
     * @return bool
     */
    protected function isKnownConfigSection($configSection)
    {
        return $this->configExtras->hasConfigSection($configSection);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataExtras()
    {
        $this->ensureMetadataExtrasInitialized();

        return $this->metadataExtras->getMetadataExtras();
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadataExtras(array $extras)
    {
        if (empty($extras)) {
            $this->metadataExtras = null;
        } else {
            if (null === $this->metadataExtras) {
                $this->metadataExtras = new MetadataExtraCollection();
            }
            $this->metadataExtras->setMetadataExtras($extras);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataExtra($extraName)
    {
        $this->ensureMetadataExtrasInitialized();

        return $this->metadataExtras->hasMetadataExtra($extraName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataExtra($extraName)
    {
        $this->ensureMetadataExtrasInitialized();

        return $this->metadataExtras->getMetadataExtra($extraName);
    }

    /**
     * {@inheritdoc}
     */
    public function addMetadataExtra(MetadataExtraInterface $extra)
    {
        $this->ensureMetadataExtrasInitialized();
        $this->metadataExtras->addMetadataExtra($extra);
    }

    /**
     * {@inheritdoc}
     */
    public function removeMetadataExtra($extraName)
    {
        $this->ensureMetadataExtrasInitialized();
        $this->metadataExtras->removeMetadataExtra($extraName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadata()
    {
        return $this->has(self::METADATA);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        if (!$this->has(self::METADATA)) {
            $this->loadMetadata();
        }

        return $this->get(self::METADATA);
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(?EntityMetadata $metadata)
    {
        if ($metadata) {
            $this->set(self::METADATA, $metadata);
        } else {
            $this->remove(self::METADATA);
        }
    }

    /**
     * Makes sure that a list of requests for additional metadata info is initialized.
     */
    private function ensureMetadataExtrasInitialized()
    {
        if (null === $this->metadataExtras) {
            $this->metadataExtras = new MetadataExtraCollection();
        }
        $action = $this->getAction();
        if ($action
            && (
                $this->metadataExtras->isEmpty()
                || !$this->metadataExtras->hasMetadataExtra(ActionMetadataExtra::NAME)
            )
        ) {
            $this->metadataExtras->addMetadataExtra(new ActionMetadataExtra($action));
        }
    }

    /**
     * Loads an entity metadata.
     */
    protected function loadMetadata()
    {
        $entityClass = $this->getClassName();
        if (empty($entityClass)) {
            $this->processLoadedMetadata(null);

            return;
        }

        try {
            $metadata = null;
            $config = $this->getConfig();
            if (null !== $config) {
                $extras = $this->getMetadataExtras();
                if ($this->isHateoasEnabled()) {
                    $extras[] = new HateoasMetadataExtra($this->getFilterValues());
                }
                $metadata = $this->metadataProvider->getMetadata(
                    $entityClass,
                    $this->getVersion(),
                    $this->getRequestType(),
                    $config,
                    $extras
                );
            }
            $this->processLoadedMetadata($metadata);
        } catch (\Exception $e) {
            $this->processLoadedMetadata(null);

            throw $e;
        }
    }

    /**
     * @param EntityMetadata|null $metadata
     */
    protected function processLoadedMetadata(?EntityMetadata $metadata)
    {
        // add loaded metadata to the context
        $this->set(self::METADATA, $metadata);
    }
}
