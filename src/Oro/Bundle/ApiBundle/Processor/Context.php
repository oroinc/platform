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
    /**
     * this header can be used to request additional data like "total count"
     * that will be returned in a response headers
     */
    public const INCLUDE_HEADER = 'X-Include';

    /** FQCN of an entity */
    private const CLASS_NAME = 'class';

    /** the response status code */
    private const RESPONSE_STATUS_CODE = 'responseStatusCode';

    /** indicates whether the current action processes a main API request */
    private const MAIN_REQUEST = 'mainRequest';

    /** indicates whether the current request is CORS request */
    private const CORS = 'cors';

    /** whether HATEOAS is enabled */
    private const HATEOAS = 'hateoas';

    /** not resolved identifiers */
    private const NOT_RESOLVED_IDENTIFIERS = 'not_resolved_identifiers';

    protected ConfigProvider $configProvider;
    protected MetadataProvider $metadataProvider;
    private ?FilterCollection $filterCollection = null;
    private ?FilterValueAccessorInterface $filterValueAccessor = null;
    private ?ParameterBagInterface $requestHeaders = null;
    private ?ParameterBagInterface $responseHeaders = null;
    private ?DocumentBuilderInterface $responseDocumentBuilder = null;
    private EntityDefinitionConfig|null|false $config = false;
    private array $configSections = [];
    private ConfigExtraCollection $configExtras;
    private EntityMetadata|null|false $metadata = false;
    private ?MetadataExtraCollection $metadataExtras = null;
    private ?array $infoRecords = null;
    private ?ParameterBagInterface $sharedData = null;
    private mixed $query = null;
    private ?Criteria $criteria = null;

    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        parent::__construct();
        $this->configExtras = new ConfigExtraCollection();
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
        $this->set(self::MAIN_REQUEST, false);
        $this->set(self::CORS, false);
        $this->set(self::HATEOAS, false);
    }

    #[\Override]
    public function getClassName(): ?string
    {
        return $this->get(self::CLASS_NAME);
    }

    #[\Override]
    public function setClassName(?string $className): void
    {
        if (null === $className) {
            $this->remove(self::CLASS_NAME);
        } else {
            $this->set(self::CLASS_NAME, $className);
        }
    }

    #[\Override]
    public function getManageableEntityClass(DoctrineHelper $doctrineHelper): ?string
    {
        return $doctrineHelper->getManageableEntityClass(
            $this->getClassName(),
            $this->getConfig()
        );
    }

    #[\Override]
    public function hasIdentifierFields(): bool
    {
        $metadata = $this->getMetadata();

        return null !== $metadata && $metadata->hasIdentifierFields();
    }

    #[\Override]
    public function getRequestHeaders(): ParameterBagInterface
    {
        if (null === $this->requestHeaders) {
            $this->requestHeaders = new CaseInsensitiveParameterBag();
        }

        return $this->requestHeaders;
    }

    #[\Override]
    public function setRequestHeaders(ParameterBagInterface $parameterBag): void
    {
        $this->requestHeaders = $parameterBag;
    }

    #[\Override]
    public function getResponseHeaders(): ParameterBagInterface
    {
        if (null === $this->responseHeaders) {
            $this->responseHeaders = new ParameterBag();
        }

        return $this->responseHeaders;
    }

    #[\Override]
    public function setResponseHeaders(ParameterBagInterface $parameterBag): void
    {
        $this->responseHeaders = $parameterBag;
    }

    #[\Override]
    public function getResponseStatusCode(): ?int
    {
        return $this->get(self::RESPONSE_STATUS_CODE);
    }

    #[\Override]
    public function setResponseStatusCode(int $statusCode): void
    {
        $this->set(self::RESPONSE_STATUS_CODE, $statusCode);
    }

    #[\Override]
    public function isSuccessResponse(): bool
    {
        $statusCode = $this->getResponseStatusCode();

        return $statusCode >= 200 && $statusCode < 300;
    }

    #[\Override]
    public function getResponseDocumentBuilder(): ?DocumentBuilderInterface
    {
        return $this->responseDocumentBuilder;
    }

    #[\Override]
    public function setResponseDocumentBuilder(?DocumentBuilderInterface $documentBuilder): void
    {
        $this->responseDocumentBuilder = $documentBuilder;
    }

    #[\Override]
    public function getFilters(): FilterCollection
    {
        if (null === $this->filterCollection) {
            $this->filterCollection = new FilterCollection();
        }

        return $this->filterCollection;
    }

    #[\Override]
    public function getFilterValues(): FilterValueAccessorInterface
    {
        if (null === $this->filterValueAccessor) {
            $this->filterValueAccessor = new FilterValueAccessor();
        }

        return $this->filterValueAccessor;
    }

    #[\Override]
    public function setFilterValues(FilterValueAccessorInterface $accessor): void
    {
        $this->filterValueAccessor = $accessor;
    }

    #[\Override]
    public function isMainRequest(): bool
    {
        return $this->get(self::MAIN_REQUEST);
    }

    #[\Override]
    public function setMainRequest(bool $main): void
    {
        $this->set(self::MAIN_REQUEST, $main);
    }

    #[\Override]
    public function isCorsRequest(): bool
    {
        return $this->get(self::CORS);
    }

    #[\Override]
    public function setCorsRequest(bool $cors): void
    {
        $this->set(self::CORS, $cors);
    }

    #[\Override]
    public function isHateoasEnabled(): bool
    {
        return (bool)$this->get(self::HATEOAS);
    }

    #[\Override]
    public function setHateoas(bool $flag): void
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

    #[\Override]
    public function getSharedData(): ParameterBagInterface
    {
        if (null === $this->sharedData) {
            $this->sharedData = new ParameterBag();
        }

        return $this->sharedData;
    }

    #[\Override]
    public function setSharedData(ParameterBagInterface $sharedData): void
    {
        $this->sharedData = $sharedData;
    }

    #[\Override]
    public function getNormalizationContext(): array
    {
        return [
            self::ACTION       => $this->getAction(),
            self::VERSION      => $this->getVersion(),
            self::REQUEST_TYPE => $this->getRequestType(),
            'sharedData'       => $this->getSharedData()
        ];
    }

    #[\Override]
    public function getInfoRecords(): ?array
    {
        return $this->infoRecords;
    }

    #[\Override]
    public function setInfoRecords(?array $infoRecords): void
    {
        $this->infoRecords = $infoRecords;
    }

    #[\Override]
    public function addInfoRecord(string $key, mixed $value): void
    {
        $this->infoRecords[$key] = $value;
    }

    #[\Override]
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

    #[\Override]
    public function getNotResolvedIdentifiers(): array
    {
        return $this->get(self::NOT_RESOLVED_IDENTIFIERS) ?? [];
    }

    #[\Override]
    public function addNotResolvedIdentifier(string $path, NotResolvedIdentifier $identifier): void
    {
        $notResolvedIdentifiers = $this->get(self::NOT_RESOLVED_IDENTIFIERS) ?? [];
        $notResolvedIdentifiers[$path] = $identifier;
        $this->set(self::NOT_RESOLVED_IDENTIFIERS, $notResolvedIdentifiers);
    }

    #[\Override]
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

    #[\Override]
    public function hasQuery(): bool
    {
        return null !== $this->query;
    }

    #[\Override]
    public function getQuery(): mixed
    {
        return $this->query;
    }

    #[\Override]
    public function setQuery(mixed $query): void
    {
        $this->query = $query;
    }

    #[\Override]
    public function getCriteria(): ?Criteria
    {
        return $this->criteria;
    }

    #[\Override]
    public function setCriteria(?Criteria $criteria): void
    {
        $this->criteria = $criteria;
    }

    #[\Override]
    public function getConfigExtras(): array
    {
        return $this->configExtras->getConfigExtras();
    }

    #[\Override]
    public function setConfigExtras(array $extras): void
    {
        $this->configExtras->setConfigExtras($extras);
        if ($this->isHateoasEnabled() && !$this->configExtras->hasConfigExtra(HateoasConfigExtra::NAME)) {
            $this->configExtras->addConfigExtra(new HateoasConfigExtra());
        }
    }

    #[\Override]
    public function hasConfigExtra(string $extraName): bool
    {
        return $this->configExtras->hasConfigExtra($extraName);
    }

    #[\Override]
    public function getConfigExtra(string $extraName): ?ConfigExtraInterface
    {
        return $this->configExtras->getConfigExtra($extraName);
    }

    #[\Override]
    public function addConfigExtra(ConfigExtraInterface $extra): void
    {
        $this->configExtras->addConfigExtra($extra);
    }

    #[\Override]
    public function removeConfigExtra(string $extraName): void
    {
        $this->configExtras->removeConfigExtra($extraName);
    }

    #[\Override]
    public function getConfigSections(): array
    {
        return $this->configExtras->getConfigSections();
    }

    #[\Override]
    public function hasConfig(): bool
    {
        return false !== $this->config;
    }

    #[\Override]
    public function getConfig(): ?EntityDefinitionConfig
    {
        if (false === $this->config) {
            $this->loadConfig();
        }

        return $this->config;
    }

    #[\Override]
    public function setConfig(?EntityDefinitionConfig $definition): void
    {
        $this->config = $definition;
        if (null === $definition) {
            $this->removeAllConfigSections();
        }
        $this->ensureAllConfigSectionsSet();
    }

    #[\Override]
    public function hasConfigOfFilters(): bool
    {
        return $this->hasConfigOf(FiltersConfigExtra::NAME);
    }

    #[\Override]
    public function getConfigOfFilters(): ?FiltersConfig
    {
        return $this->getConfigOf(FiltersConfigExtra::NAME);
    }

    #[\Override]
    public function setConfigOfFilters(?FiltersConfig $config): void
    {
        $this->setConfigOf(FiltersConfigExtra::NAME, $config);
    }

    #[\Override]
    public function hasConfigOfSorters(): bool
    {
        return $this->hasConfigOf(SortersConfigExtra::NAME);
    }

    #[\Override]
    public function getConfigOfSorters(): ?SortersConfig
    {
        return $this->getConfigOf(SortersConfigExtra::NAME);
    }

    #[\Override]
    public function setConfigOfSorters(?SortersConfig $config): void
    {
        $this->setConfigOf(SortersConfigExtra::NAME, $config);
    }

    #[\Override]
    public function hasConfigOf(string $configSection): bool
    {
        if (!$this->isKnownConfigSection($configSection)) {
            return false;
        }

        return \array_key_exists($configSection, $this->configSections);
    }

    #[\Override]
    public function getConfigOf(string $configSection): mixed
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

    #[\Override]
    public function setConfigOf(string $configSection, mixed $config): void
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

    protected function loadEntityConfig(string $entityClass, array $extras): Config
    {
        return $this->configProvider->getConfig(
            $entityClass,
            $this->getVersion(),
            $this->getRequestType(),
            $extras
        );
    }

    /**
     * Loads an entity configuration.
     */
    protected function loadConfig(): void
    {
        $entityClass = $this->getClassName();
        if (!$entityClass) {
            $this->processLoadedConfig(null);

            throw new RuntimeException('A class name must be set in the context before a configuration is loaded.');
        }

        try {
            $config = $this->loadEntityConfig($entityClass, $this->getConfigExtras());
            $this->processLoadedConfig($config);
        } catch (\Exception $e) {
            $this->processLoadedConfig(null);

            throw $e;
        }
    }

    protected function processLoadedConfig(?Config $config): void
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
    protected function ensureAllConfigSectionsSet(): void
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
    protected function removeAllConfigSections(): void
    {
        $configSections = $this->getConfigSections();
        foreach ($configSections as $name) {
            if (\array_key_exists($name, $this->configSections)) {
                unset($this->configSections[$name]);
            }
        }
    }

    protected function isKnownConfigSection(string $configSection): bool
    {
        return $this->configExtras->hasConfigSection($configSection);
    }

    #[\Override]
    public function getMetadataExtras(): array
    {
        $this->ensureMetadataExtrasInitialized();

        return $this->metadataExtras->getMetadataExtras();
    }

    #[\Override]
    public function setMetadataExtras(array $extras): void
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

    #[\Override]
    public function hasMetadataExtra(string $extraName): bool
    {
        $this->ensureMetadataExtrasInitialized();

        return $this->metadataExtras->hasMetadataExtra($extraName);
    }

    #[\Override]
    public function getMetadataExtra(string $extraName): ?MetadataExtraInterface
    {
        $this->ensureMetadataExtrasInitialized();

        return $this->metadataExtras->getMetadataExtra($extraName);
    }

    #[\Override]
    public function addMetadataExtra(MetadataExtraInterface $extra): void
    {
        $this->ensureMetadataExtrasInitialized();
        $this->metadataExtras->addMetadataExtra($extra);
    }

    #[\Override]
    public function removeMetadataExtra(string $extraName): void
    {
        $this->ensureMetadataExtrasInitialized();
        $this->metadataExtras->removeMetadataExtra($extraName);
    }

    #[\Override]
    public function hasMetadata(): bool
    {
        return false !== $this->metadata;
    }

    #[\Override]
    public function getMetadata(): ?EntityMetadata
    {
        if (false === $this->metadata) {
            $this->loadMetadata();
        }

        return $this->metadata;
    }

    #[\Override]
    public function setMetadata(?EntityMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Makes sure that a list of requests for additional metadata info is initialized.
     */
    private function ensureMetadataExtrasInitialized(): void
    {
        if (null === $this->metadataExtras) {
            $this->metadataExtras = new MetadataExtraCollection();
        }
        $action = $this->get(self::ACTION);
        if ($action
            && (
                $this->metadataExtras->isEmpty()
                || !$this->metadataExtras->hasMetadataExtra(ActionMetadataExtra::NAME)
            )
        ) {
            $this->metadataExtras->addMetadataExtra($this->createActionMetadataExtra($action));
        }
        if ($this->isHateoasEnabled() && !$this->metadataExtras->hasMetadataExtra(HateoasMetadataExtra::NAME)) {
            $this->metadataExtras->addMetadataExtra(new HateoasMetadataExtra($this->getFilterValues()));
        }
    }

    protected function createActionMetadataExtra(string $action): ActionMetadataExtra
    {
        return new ActionMetadataExtra($action);
    }

    /**
     * Loads an entity metadata.
     */
    protected function loadMetadata(): void
    {
        $entityClass = $this->getClassName();
        if (!$entityClass) {
            $this->processLoadedMetadata(null);

            return;
        }

        try {
            $config = $this->getConfig();
            if (null !== $config) {
                $metadata = $this->metadataProvider->getMetadata(
                    $entityClass,
                    $this->getVersion(),
                    $this->getRequestType(),
                    $config,
                    $this->getMetadataExtras()
                );
                if (null !== $metadata) {
                    $this->initializeMetadata($metadata);
                }
                $this->processLoadedMetadata($metadata);
            } else {
                $this->processLoadedMetadata(null);
            }
        } catch (\Exception $e) {
            $this->processLoadedMetadata(null);

            throw $e;
        }
    }

    protected function processLoadedMetadata(?EntityMetadata $metadata): void
    {
        // add loaded metadata to the context
        $this->metadata = $metadata;
    }

    protected function initializeMetadata(
        EntityMetadata $metadata,
        ?string $path = null,
        ?TargetMetadataAccessor $targetMetadataAccessor = null
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

        $this->setTargetMetadataAccessor($metadata, $targetMetadataAccessor, $path);
    }

    protected function setTargetMetadataAccessor(
        EntityMetadata $metadata,
        ?TargetMetadataAccessor $targetMetadataAccessor,
        ?string $path = null
    ): void {
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
