<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Bundle\ApiBundle\Collection\CaseInsensitiveParameterBag;
use Oro\Bundle\ApiBundle\Config\Config;
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
class Context extends ApiContext implements ContextInterface
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
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
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
    public function getFilters()
    {
        if (!$this->has(self::FILTERS)) {
            $this->set(self::FILTERS, new FilterCollection());
        }

        return $this->get(self::FILTERS);
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
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return null !== $this->errors
            ? $this->errors
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function addError(Error $error)
    {
        if (null === $this->errors) {
            $this->errors = [];
        }
        $this->errors[] = $error;
    }

    /**
     * {@inheritdoc}
     */
    public function resetErrors()
    {
        $this->errors = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigExtras()
    {
        $extras = $this->get(self::CONFIG_EXTRAS);

        return null !== $extras
            ? $extras
            : [];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getConfigExtra($extraName)
    {
        $configExtras = $this->getConfigExtras();
        foreach ($configExtras as $configExtra) {
            if ($configExtra->getName() === $extraName) {
                return $configExtra;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfigExtra(ConfigExtraInterface $extra)
    {
        if ($this->hasConfigExtra($extra->getName())) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" config extra already exists.', $extra->getName())
            );
        }
        $extras = $this->getConfigExtras();
        $extras[] = $extra;
        $this->setConfigExtras($extras);
    }

    /**
     * {@inheritdoc}
     */
    public function removeConfigExtra($extraName)
    {
        $configExtras = $this->getConfigExtras();
        $keys = array_keys($configExtras);
        foreach ($keys as $key) {
            if ($configExtras[$key]->getName() === $extraName) {
                unset($configExtras[$key]);
            }
        }
        $this->setConfigExtras(array_values($configExtras));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigSections()
    {
        $sections = [];
        $configExtras = $this->getConfigExtras();
        foreach ($configExtras as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface) {
                $sections[] = $configExtra->getName();
            }
        }

        return $sections;
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
    public function setConfig(EntityDefinitionConfig $definition = null)
    {
        if ($definition) {
            $this->set($this->getConfigKey(), $definition);
            // make sure that all config sections are added to the context
            $this->ensureAllConfigSectionsSet();
        } else {
            $this->remove($this->getConfigKey());
            // make sure that all config sections are removed from the context
            $this->ensureAllConfigSectionsSet(true);
        }
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
    public function setConfigOfFilters(FiltersConfig $config = null)
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
    public function setConfigOfSorters(SortersConfig $config = null)
    {
        $this->setConfigOf(SortersConfigExtra::NAME, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfigOf($configSection)
    {
        $this->assertConfigSection($configSection);

        return $this->has(self::CONFIG_PREFIX . $configSection);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
            $this->processLoadedConfig(null);

            throw new \RuntimeException(
                'A class name must be set in the context before a configuration is loaded.'
            );
        }

        try {
            $config = $this->configProvider->getConfig(
                $entityClass,
                $this->getVersion(),
                $this->getRequestType(),
                $this->getConfigExtras()
            );
            $this->processLoadedConfig($config);
        } catch (\Exception $e) {
            $this->processLoadedConfig(null);

            throw $e;
        }
    }

    /**
     * @param Config|null $config
     */
    protected function processLoadedConfig(Config $config = null)
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
     * Makes sure that all config sections are added to (or removed from) the context.
     *
     * @param bool $remove
     */
    protected function ensureAllConfigSectionsSet($remove = false)
    {
        $configExtras = $this->getConfigExtras();
        foreach ($configExtras as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface) {
                $key = self::CONFIG_PREFIX . $configExtra->getName();
                if ($remove) {
                    if ($this->has($key)) {
                        $this->remove($key);
                    }
                } else {
                    if (!$this->has($key)) {
                        $this->set($key, null);
                    }
                }
            }
        }
    }

    /**
     * @param string $configSection
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    protected function assertConfigSection($configSection)
    {
        $valid = false;
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

    /**
     * {@inheritdoc}
     */
    public function getMetadataExtras()
    {
        $extras = $this->get(self::METADATA_EXTRAS);

        return null !== $extras
            ? $extras
            : [];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function addMetadataExtra(MetadataExtraInterface $extra)
    {
        if ($this->hasMetadataExtra($extra->getName())) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" metadata extra already exists.', $extra->getName())
            );
        }
        $extras = $this->getMetadataExtras();
        $extras[] = $extra;
        $this->setMetadataExtras($extras);
    }

    /**
     * {@inheritdoc}
     */
    public function removeMetadataExtra($extraName)
    {
        $metadataExtras = $this->getMetadataExtras();
        $keys = array_keys($metadataExtras);
        foreach ($keys as $key) {
            if ($metadataExtras[$key]->getName() === $extraName) {
                unset($metadataExtras[$key]);
            }
        }
        $this->setMetadataExtras(array_values($metadataExtras));
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
    public function setMetadata(EntityMetadata $metadata = null)
    {
        if ($metadata) {
            $this->set(self::METADATA, $metadata);
        } else {
            $this->remove(self::METADATA);
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
            $metadata = $this->metadataProvider->getMetadata(
                $entityClass,
                $this->getVersion(),
                $this->getRequestType(),
                $this->getMetadataExtras(),
                $this->getConfig()
            );
            $this->processLoadedMetadata($metadata);
        } catch (\Exception $e) {
            $this->processLoadedMetadata(null);

            throw $e;
        }
    }

    /**
     * @param EntityMetadata|null $metadata
     */
    protected function processLoadedMetadata(EntityMetadata $metadata = null)
    {
        // add loaded metadata to the context
        $this->set(self::METADATA, $metadata);
    }
}
