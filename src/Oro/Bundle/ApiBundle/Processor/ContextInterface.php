<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * Represents an execution context for API processors for public actions.
 */
interface ContextInterface extends SharedDataAwareContextInterface
{
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
     * Returns the API resource class if it is a manageable entity;
     * otherwise, checks if the API resource is based on a manageable entity, and if so,
     * returns the class name of this entity.
     * If both the API resource class and its parent are not manageable entities, returns NULL.
     *
     * @param DoctrineHelper $doctrineHelper
     *
     * @return string|null
     */
    public function getManageableEntityClass(DoctrineHelper $doctrineHelper);

    /**
     * Checks whether metadata of an entity has at least one identifier field.
     *
     * @return bool
     */
    public function hasIdentifierFields();

    /**
     * Gets request headers.
     *
     * @return ParameterBagInterface
     */
    public function getRequestHeaders();

    /**
     * Sets an object that will be used to accessing request headers.
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
     * @param int $statusCode
     */
    public function setResponseStatusCode($statusCode);

    /**
     * Indicates whether a result document represents a success response.
     *
     * @return bool
     */
    public function isSuccessResponse();

    /**
     * Gets the response document builder.
     *
     * @return DocumentBuilderInterface|null
     */
    public function getResponseDocumentBuilder();

    /**
     * Sets the response document builder.
     */
    public function setResponseDocumentBuilder(?DocumentBuilderInterface $documentBuilder);

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
     */
    public function setFilterValues(FilterValueAccessorInterface $accessor);

    /**
     * Indicates whether the current action processes a master API request
     * or it is executed as part of another action.
     */
    public function isMasterRequest(): bool;

    /**
     * Sets a flag indicates whether the current action processes a master API request
     * or it is executed as part of another action.
     */
    public function setMasterRequest(bool $master): void;

    /**
     * Indicates whether the current request is CORS request.
     * @link https://www.w3.org/TR/cors/
     */
    public function isCorsRequest(): bool;

    /**
     * Sets a flag indicates whether the current request is CORS request.
     * @link https://www.w3.org/TR/cors/
     */
    public function setCorsRequest(bool $cors): void;

    /**
     * Indicates whether HATEOAS is enabled.
     */
    public function isHateoasEnabled(): bool;

    /**
     * Sets a flag indicates whether HATEOAS is enabled.
     */
    public function setHateoas(bool $flag);

    /**
     * Gets a context for response data normalization.
     */
    public function getNormalizationContext(): array;

    /**
     * Gets a list of records contains an additional information about collections,
     * e.g. "has_more" flag in such record indicates whether a collection has more records than it was requested.
     *
     * @return array|null [property path => info record, ...]
     */
    public function getInfoRecords(): ?array;

    /**
     * Sets a list of records contains an additional information about collections,
     * e.g. "has_more" flag in such record indicates whether a collection has more records than it was requested.
     *
     * Important: do not use array_merge() function to merge existing records with new ones
     * because the array of records can contain an element with empty string as the key
     * and it will be lost by array_merge() function.
     * Use addInfoRecord() method to add new records instead.
     *
     * @param array|null $infoRecords [property path => info record, ...]
     */
    public function setInfoRecords(?array $infoRecords): void;

    /**
     * Adds a record that contains an additional information about collections.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function addInfoRecord(string $key, $value): void;

    /**
     * Adds records that contain an additional information about a collection valued association.
     */
    public function addAssociationInfoRecords(string $propertyPath, array $infoRecords): void;

    /**
     * Gets all not resolved identifiers.
     *
     * @return NotResolvedIdentifier[] [path => identifier, ...]
     */
    public function getNotResolvedIdentifiers(): array;

    /**
     * Adds an identifier that cannot be resolved.
     *
     * @param string $path          The path, e.g. "entityId", "filters.owner", "requestData.data.id"
     * @param NotResolvedIdentifier $identifier The submitted identifier
     */
    public function addNotResolvedIdentifier(string $path, NotResolvedIdentifier $identifier): void;

    /**
     * Removes an identifier that cannot be resolved.
     *
     * @param string $path The path, e.g. "entityId", "filters.owner", "requestData.data.id"
     */
    public function removeNotResolvedIdentifier(string $path): void;

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
     */
    public function setCriteria(Criteria $criteria = null);

    /**
     * Gets all entities, primary and included ones, that are processing by an action.
     *
     * @param bool $primaryOnly Whether only primary entities or both primary and included entities should be returned
     *
     * @return object[]
     */
    public function getAllEntities(bool $primaryOnly = false): array;

    /**
     * Whether any error occurred when processing an action.
     *
     * @return bool
     */
    public function hasErrors();

    /**
     * Gets all errors occurred when processing an action.
     *
     * @return Error[]
     */
    public function getErrors();

    /**
     * Registers an error.
     */
    public function addError(Error $error);

    /**
     * Removes all errors.
     */
    public function resetErrors();

    /**
     * Gets a value indicates whether errors should just stop processing
     * or an exception should be thrown is any error occurred.
     *
     * @return bool
     */
    public function isSoftErrorsHandling();

    /**
     * Sets a value indicates whether errors should just stop processing
     * or an exception should be thrown is any error occurred.
     *
     * @param bool $softErrorsHandling
     */
    public function setSoftErrorsHandling($softErrorsHandling);

    /**
     * Marks a work as already done.
     * In the most cases this method is useless because it is easy to determine
     * when a work is already done just checking a state of a context.
     * But if a processor does a complex work, it might be required
     * to directly mark the work as already done.
     *
     * @param string $operationName The name of an operation that represents some work
     */
    public function setProcessed($operationName);

    /**
     * Marks a work as not done yet.
     *
     * @param string $operationName The name of an operation that represents some work
     */
    public function clearProcessed($operationName);

    /**
     * Checks whether a work is already done.
     *
     * @param string $operationName The name of an operation that represents some work
     *
     * @return bool
     */
    public function isProcessed($operationName);

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
     */
    public function setConfig(?EntityDefinitionConfig $definition);

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
     */
    public function setConfigOfFilters(?FiltersConfig $config);

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
     */
    public function setConfigOfSorters(?SortersConfig $config);

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
     * Gets a request for some additional metadata info by its name.
     *
     * @param string $extraName
     *
     * @return MetadataExtraInterface|null
     */
    public function getMetadataExtra($extraName);

    /**
     * Adds a request for some additional metadata info.
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
     */
    public function setMetadata(?EntityMetadata $metadata);
}
