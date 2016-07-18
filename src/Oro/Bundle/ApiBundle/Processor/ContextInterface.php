<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\RequestType;

interface ContextInterface extends ComponentContextInterface
{
    /**
     * Gets the current request type.
     * A request can belong to several types, e.g. "rest" and "json_api".
     *
     * @return RequestType
     */
    public function getRequestType();

    /**
     * Gets API version
     *
     * @return string
     */
    public function getVersion();

    /**
     * Sets API version
     *
     * @param string $version
     */
    public function setVersion($version);

    /**
     * Gets FQCN of an entity.
     *
     * @return string
     */
    public function getClassName();

    /**
     * Sets FQCN of an entity.
     *
     * @param string $className
     */
    public function setClassName($className);

    /**
     * Gets request headers.
     *
     * @return ParameterBagInterface
     */
    public function getRequestHeaders();

    /**
     * Sets an object that will be used to accessing request headers.
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setRequestHeaders(ParameterBagInterface $parameterBag);

    /**
     * Gets response headers.
     *
     * @return ParameterBagInterface
     */
    public function getResponseHeaders();

    /**
     * Sets an object that will be used to accessing response headers.
     *
     * @param ParameterBagInterface $parameterBag
     */
    public function setResponseHeaders(ParameterBagInterface $parameterBag);

    /**
     * Gets the response status code.
     *
     * @return int|null
     */
    public function getResponseStatusCode();

    /**
     * Sets the response status code.
     *
     * @param $statusCode
     */
    public function setResponseStatusCode($statusCode);

    /**
     * Indicates whether a result document represents a success response.
     *
     * @return int|null
     */
    public function isSuccessResponse();

    /**
     * Gets a list of filters is used to add additional restrictions to a query is used to get result data.
     *
     * @return FilterCollection
     */
    public function getFilters();

    /**
     * Gets a collection of the FilterValue objects that contains all incoming filters.
     *
     * @return FilterValueAccessorInterface
     */
    public function getFilterValues();

    /**
     * Sets an object that will be used to accessing incoming filters.
     *
     * @param FilterValueAccessorInterface $accessor
     */
    public function setFilterValues(FilterValueAccessorInterface $accessor);

    /**
     * Checks whether a query is used to get result data exists.
     *
     * @return bool
     */
    public function hasQuery();

    /**
     * Gets a query is used to get result data.
     *
     * @return mixed
     */
    public function getQuery();

    /**
     * Sets a query is used to get result data.
     *
     * @param mixed $query
     */
    public function setQuery($query);

    /**
     * Gets the Criteria object is used to add additional restrictions to a query is used to get result data.
     *
     * @return Criteria|null
     */
    public function getCriteria();

    /**
     * Sets the Criteria object is used to add additional restrictions to a query is used to get result data.
     *
     * @param Criteria $criteria
     */
    public function setCriteria($criteria);

    /**
     * Whether any error happened during the processing of an action.
     *
     * @return bool
     */
    public function hasErrors();

    /**
     * Gets all errors happened during the processing of an action.
     *
     * @return Error[]
     */
    public function getErrors();

    /**
     * Registers an error.
     *
     * @param Error $error
     */
    public function addError(Error $error);

    /**
     * Removes all errors.
     */
    public function resetErrors();

    /**
     * Gets a list of requests for configuration data.
     *
     * @return ConfigExtraInterface[]
     */
    public function getConfigExtras();

    /**
     * Sets a list of requests for configuration data.
     *
     * @param ConfigExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setConfigExtras(array $extras);

    /**
     * Checks whether some configuration data is requested.
     *
     * @param string $extraName
     *
     * @return bool
     */
    public function hasConfigExtra($extraName);

    /**
     * Gets a request for configuration data by its name.
     *
     * @param string $extraName
     *
     * @return ConfigExtraInterface|null
     */
    public function getConfigExtra($extraName);

    /**
     * Adds a request for some configuration data.
     *
     * @param ConfigExtraInterface $extra
     *
     * @throws \InvalidArgumentException if a config extra with the same name already exists
     */
    public function addConfigExtra(ConfigExtraInterface $extra);

    /**
     * Removes a request for some configuration data.
     *
     * @param string $extraName
     */
    public function removeConfigExtra($extraName);

    /**
     * Gets names of all requested configuration sections.
     *
     * @return string[]
     */
    public function getConfigSections();

    /**
     * Checks whether a configuration of an entity exists.
     *
     * @return bool
     */
    public function hasConfig();

    /**
     * Gets a configuration of an entity.
     *
     * @return EntityDefinitionConfig|null
     */
    public function getConfig();

    /**
     * Sets a configuration of an entity.
     *
     * @param EntityDefinitionConfig|null $definition
     */
    public function setConfig(EntityDefinitionConfig $definition = null);

    /**
     * Checks whether a configuration of filters for an entity exists.
     *
     * @return bool
     */
    public function hasConfigOfFilters();

    /**
     * Gets a configuration of filters for an entity.
     *
     * @return FiltersConfig|null
     */
    public function getConfigOfFilters();

    /**
     * Sets a configuration of filters for an entity.
     *
     * @param FiltersConfig|null $config
     */
    public function setConfigOfFilters(FiltersConfig $config = null);

    /**
     * Checks whether a configuration of sorters for an entity exists.
     *
     * @return bool
     */
    public function hasConfigOfSorters();

    /**
     * Gets a configuration of sorters for an entity.
     *
     * @return SortersConfig|null
     */
    public function getConfigOfSorters();

    /**
     * Sets a configuration of sorters for an entity.
     *
     * @param SortersConfig|null $config
     */
    public function setConfigOfSorters(SortersConfig $config = null);

    /**
     * Checks whether a configuration of the given section exists.
     *
     * @param string $configSection
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function hasConfigOf($configSection);

    /**
     * Gets a configuration from the given section.
     *
     * @param string $configSection
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function getConfigOf($configSection);

    /**
     * Sets a configuration for the given section.
     *
     * @param string $configSection
     * @param mixed  $config
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function setConfigOf($configSection, $config);

    /**
     * Gets a list of requests for additional metadata info.
     *
     * @return MetadataExtraInterface[]
     */
    public function getMetadataExtras();

    /**
     * Sets a list of requests for additional metadata info.
     *
     * @param MetadataExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setMetadataExtras(array $extras);

    /**
     * Checks whether some additional metadata info is requested.
     *
     * @param string $extraName
     *
     * @return bool
     */
    public function hasMetadataExtra($extraName);

    /**
     * Adds a request for some additional metadata info.
     *
     * @param MetadataExtraInterface $extra
     *
     * @throws \InvalidArgumentException if a metadata extra with the same name already exists
     */
    public function addMetadataExtra(MetadataExtraInterface $extra);

    /**
     * Removes a request for some additional metadata info.
     *
     * @param string $extraName
     */
    public function removeMetadataExtra($extraName);

    /**
     * Checks whether metadata of an entity exists.
     *
     * @return bool
     */
    public function hasMetadata();

    /**
     * Gets metadata of an entity.
     *
     * @return EntityMetadata|null
     */
    public function getMetadata();

    /**
     * Sets metadata of an entity.
     *
     * @param EntityMetadata|null $metadata
     */
    public function setMetadata(EntityMetadata $metadata = null);
}
