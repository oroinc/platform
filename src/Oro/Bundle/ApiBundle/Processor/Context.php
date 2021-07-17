<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Collection\CaseInsensitiveParameterBag;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraCollection;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessor;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraCollection;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Metadata\TargetMetadataAccessor;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * The base execution context for API processors for public actions.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Context extends NormalizeResultContext implements ContextInterface
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

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

    /** not resolved identifiers */
    private const NOT_RESOLVED_IDENTIFIERS = 'not_resolved_identifiers';

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

    /** @var EntityDefinitionConfig|null|bool */
    private $config = false;

    /** @var array */
    private $configSections = [];

    /** @var ConfigExtraCollection */
    private $configExtras;

    /** @var EntityMetadata|null|bool */
    private $metadata = false;

    /** @var MetadataExtraCollection|null */
    private $metadataExtras;

    /** @var array|null */
    private $infoRecords;

    /** @var ParameterBagInterface|null */
    private $sharedData;

    /** @var mixed */
    private $query;

    /** @var Criteria|null */
    private $criteria;

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
    public function getManageableEntityClass(DoctrineHelper $doctrineHelper)
    {
        return $doctrineHelper->getManageableEntityClass(
            $this->getClassName(),
            $this->getConfig()
        );
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

        return $statusCode >= 200 && $statusCode < 300;
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
            $this->filterValues = new FilterValueAccessor();
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
        if (!$flag) {
            $this->configExtras->removeConfigExtra(HateoasConfigExtra::NAME);
        } elseif (!$this->configExtras->hasConfigExtra(HateoasConfigExtra::NAME)) {
            $this->configExtras->addConfigExtra(new HateoasConfigExtra());
        }
        if (null !== $this->metadataExtras) {
            if (!$flag) {
                $this->metadataExtras->removeMetadataExtra(HateoasMetadataExtra::NAME);
            } elseif (!$this->metadataExtras->hasMetadataExtra(HateoasMetadataExtra::NAME)) {
                $this->metadataExtras->addMetadataExtra(new HateoasMetadataExtra($this->getFilterValues()));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedData(): ParameterBagInterface
    {
        if (null === $this->sharedData) {
            $this->sharedData = new ParameterBag();
        }

        return $this->sharedData;
    }

    /**
     * {@inheritdoc}
     */
    public function setSharedData(ParameterBagInterface $sharedData): void
    {
        $this->sharedData = $sharedData;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizationContext(): array
    {
        return [
            self::ACTION       => $this->getAction(),
            self::VERSION      => $this->getVersion(),
            self::REQUEST_TYPE => $this->getRequestType(),
            'sharedData'       => $this->getSharedData()
        ];
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
    public function addInfoRecord(string $key, $value): void
    {
        $this->infoRecords[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function addAssociationInfoRecords(string $propertyPath, array $infoRecords): void
    {
        foreach ($infoRecords as $key => $val) {
            if ($key) {
                $this->infoRecords[$propertyPath][$key] = $val;
            } elseif (\is_array($val)) {
                foreach ($val as $k => $v) {
                    $this->infoRecords[$propertyPath][$k] = $v;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNotResolvedIdentifiers(): array
    {
        return $this->get(self::NOT_RESOLVED_IDENTIFIERS) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function addNotResolvedIdentifier(string $path, NotResolvedIdentifier $identifier): void
    {
        $notResolvedIdentifiers = $this->get(self::NOT_RESOLVED_IDENTIFIERS) ?? [];
        $notResolvedIdentifiers[$path] = $identifier;
        $this->set(self::NOT_RESOLVED_IDENTIFIERS, $notResolvedIdentifiers);
    }

    /**
     * {@inheritdoc}
     */
    public function removeNotResolvedIdentifier(string $path): void
    {
        $notResolvedIdentifiers = $this->get(self::NOT_RESOLVED_IDENTIFIERS);
        if ($notResolvedIdentifiers) {
            unset($notResolvedIdentifiers[$path]);
            if ($notResolvedIdentifiers) {
                $this->set(self::NOT_RESOLVED_IDENTIFIERS, $notResolvedIdentifiers);
            } else {
                $this->remove(self::NOT_RESOLVED_IDENTIFIERS);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasQuery()
    {
        return null !== $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * {@inheritdoc}
     */
    public function setCriteria(Criteria $criteria = null)
    {
        $this->criteria = $criteria;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllEntities(bool $primaryOnly = false): array
    {
        throw new \LogicException('The method is not implemented for this context.');
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
        if ($this->isHateoasEnabled() && !$this->configExtras->hasConfigExtra(HateoasConfigExtra::NAME)) {
            $this->configExtras->addConfigExtra(new HateoasConfigExtra());
        }
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
        return false !== $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        if (false === $this->config) {
            $this->loadConfig();
        }

        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(?EntityDefinitionConfig $definition)
    {
        $this->config = $definition;
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

        return \array_key_exists($configSection, $this->configSections);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigOf($configSection)
    {
        if (!$this->isKnownConfigSection($configSection)) {
            return null;
        }

        if (!\array_key_exists($configSection, $this->configSections)) {
            if (false !== $this->config) {
                $this->setConfigOf($configSection, null);
            } else {
                $this->loadConfig();
            }
        }

        return $this->configSections[$configSection];
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigOf($configSection, $config)
    {
        if (!$this->isKnownConfigSection($configSection)) {
            throw new \InvalidArgumentException(sprintf('Undefined configuration section: "%s".', $configSection));
        }

        $this->configSections[$configSection] = $config;

        // make sure that all config sections, including a main section, are added to the context
        if (false === $this->config) {
            $this->config = null;
        }
        $this->ensureAllConfigSectionsSet();
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

    protected function processLoadedConfig(?Config $config)
    {
        // add loaded config sections to the context
        if ($config && !$config->isEmpty()) {
            foreach ($config as $key => $value) {
                if (ConfigUtil::DEFINITION === $key) {
                    $this->config = $value;
                } else {
                    $this->configSections[$key] = $value;
                }
            }
        }

        // make sure that all config sections, including a main section, are added to the context
        // even if a section was not returned by the config provider
        if (false === $this->config) {
            $this->config = null;
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
            if (!\array_key_exists($name, $this->configSections)) {
                $this->configSections[$name] = null;
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
            if (\array_key_exists($name, $this->configSections)) {
                unset($this->configSections[$name]);
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
            if ($this->isHateoasEnabled() && !$this->metadataExtras->hasMetadataExtra(HateoasMetadataExtra::NAME)) {
                $this->metadataExtras->addMetadataExtra(new HateoasMetadataExtra($this->getFilterValues()));
            }
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
        return false !== $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        if (false === $this->metadata) {
            $this->loadMetadata();
        }

        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(?EntityMetadata $metadata)
    {
        if ($metadata) {
            $this->metadata = $metadata;
        } else {
            $this->metadata = false;
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
        if ($this->isHateoasEnabled() && !$this->metadataExtras->hasMetadataExtra(HateoasMetadataExtra::NAME)) {
            $this->metadataExtras->addMetadataExtra(new HateoasMetadataExtra($this->getFilterValues()));
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
                $metadata = $this->metadataProvider->getMetadata(
                    $entityClass,
                    $this->getVersion(),
                    $this->getRequestType(),
                    $config,
                    $this->getMetadataExtras()
                );
                $this->initializeMetadata($metadata);
            }
            $this->processLoadedMetadata($metadata);
        } catch (\Exception $e) {
            $this->processLoadedMetadata(null);

            throw $e;
        }
    }

    protected function processLoadedMetadata(?EntityMetadata $metadata)
    {
        // add loaded metadata to the context
        $this->metadata = $metadata;
    }

    private function initializeMetadata(
        EntityMetadata $metadata,
        string $path = null,
        TargetMetadataAccessor $targetMetadataAccessor = null
    ): void {
        if (null === $targetMetadataAccessor) {
            $targetMetadataAccessor = new TargetMetadataAccessor(
                $this->getVersion(),
                $this->getRequestType(),
                $this->metadataProvider,
                $this->getMetadataExtras(),
                $this->configProvider,
                $this->getConfigExtras()
            );
        }

        $metadata->setTargetMetadataAccessor($targetMetadataAccessor);

        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            $associationPath = $path
                ? $path . ConfigUtil::PATH_DELIMITER . $associationName
                : $associationName;
            $association->setTargetMetadataAccessor($targetMetadataAccessor);
            $association->setAssociationPath($associationPath);
            $targetMetadata = $association->getTargetMetadata();
            if (null !== $targetMetadata) {
                $this->initializeMetadata($targetMetadata, $associationPath, $targetMetadataAccessor);
            }
        }
    }
}
