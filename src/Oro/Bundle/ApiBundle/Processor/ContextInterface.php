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
     */
    public function getClassName(): ?string;

    /**
     * Sets FQCN of an entity.
     */
    public function setClassName(string $className): void;

    /**
     * Returns the API resource class if it is a manageable entity;
     * otherwise, checks if the API resource is based on a manageable entity, and if so,
     * returns the class name of this entity.
     * If both the API resource class and its parent are not manageable entities, returns NULL.
     */
    public function getManageableEntityClass(DoctrineHelper $doctrineHelper): ?string;

    /**
     * Checks whether metadata of an entity has at least one identifier field.
     */
    public function hasIdentifierFields(): bool;

    /**
     * Gets request headers.
     */
    public function getRequestHeaders(): ParameterBagInterface;

    /**
     * Sets an object that will be used to accessing request headers.
     */
    public function setRequestHeaders(ParameterBagInterface $parameterBag): void;

    /**
     * Gets response headers.
     */
    public function getResponseHeaders(): ParameterBagInterface;

    /**
     * Sets an object that will be used to accessing response headers.
     */
    public function setResponseHeaders(ParameterBagInterface $parameterBag): void;

    /**
     * Gets the response status code.
     */
    public function getResponseStatusCode(): ?int;

    /**
     * Sets the response status code.
     */
    public function setResponseStatusCode(int $statusCode): void;

    /**
     * Indicates whether a result document represents a success response.
     */
    public function isSuccessResponse(): bool;

    /**
     * Gets the response document builder.
     */
    public function getResponseDocumentBuilder(): ?DocumentBuilderInterface;

    /**
     * Sets the response document builder.
     */
    public function setResponseDocumentBuilder(?DocumentBuilderInterface $documentBuilder): void;

    /**
     * Gets a list of filters is used to add additional restrictions to a query is used to get result data.
     */
    public function getFilters(): FilterCollection;

    /**
     * Gets a collection of the FilterValue objects that contains all incoming filters.
     */
    public function getFilterValues(): FilterValueAccessorInterface;

    /**
     * Sets an object that will be used to accessing incoming filters.
     */
    public function setFilterValues(FilterValueAccessorInterface $accessor): void;

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
    public function setHateoas(bool $flag): void;

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
     */
    public function addInfoRecord(string $key, mixed $value): void;

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
     */
    public function hasQuery(): bool;

    /**
     * Gets a query is used to get result data.
     */
    public function getQuery(): mixed;

    /**
     * Sets a query is used to get result data.
     */
    public function setQuery(mixed $query): void;

    /**
     * Gets the Criteria object is used to add additional restrictions to a query is used to get result data.
     */
    public function getCriteria(): ?Criteria;

    /**
     * Sets the Criteria object is used to add additional restrictions to a query is used to get result data.
     */
    public function setCriteria(?Criteria $criteria): void;

    /**
     * Whether any error occurred when processing an action.
     */
    public function hasErrors(): bool;

    /**
     * Gets all errors occurred when processing an action.
     *
     * @return Error[]
     */
    public function getErrors(): array;

    /**
     * Registers an error.
     */
    public function addError(Error $error): void;

    /**
     * Removes all errors.
     */
    public function resetErrors(): void;

    /**
     * Gets a value indicates whether errors should just stop processing
     * or an exception should be thrown is any error occurred.
     */
    public function isSoftErrorsHandling(): bool;

    /**
     * Sets a value indicates whether errors should just stop processing
     * or an exception should be thrown is any error occurred.
     */
    public function setSoftErrorsHandling(bool $softErrorsHandling): void;

    /**
     * Marks a work as already done.
     * In the most cases this method is useless because it is easy to determine
     * when a work is already done just checking a state of a context.
     * But if a processor does a complex work, it might be required
     * to directly mark the work as already done.
     */
    public function setProcessed(string $operationName): void;

    /**
     * Marks a work as not done yet.
     */
    public function clearProcessed(string $operationName): void;

    /**
     * Checks whether a work is already done.
     */
    public function isProcessed(string $operationName): bool;

    /**
     * Gets a list of requests for configuration data.
     *
     * @return ConfigExtraInterface[]
     */
    public function getConfigExtras(): array;

    /**
     * Sets a list of requests for configuration data.
     *
     * @param ConfigExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setConfigExtras(array $extras): void;

    /**
     * Checks whether some configuration data is requested.
     */
    public function hasConfigExtra(string $extraName): bool;

    /**
     * Gets a request for configuration data by its name.
     */
    public function getConfigExtra(string $extraName): ?ConfigExtraInterface;

    /**
     * Adds a request for some configuration data.
     *
     * @throws \InvalidArgumentException if a config extra with the same name already exists
     */
    public function addConfigExtra(ConfigExtraInterface $extra): void;

    /**
     * Removes a request for some configuration data.
     */
    public function removeConfigExtra(string $extraName): void;

    /**
     * Gets names of all requested configuration sections.
     *
     * @return string[]
     */
    public function getConfigSections(): array;

    /**
     * Checks whether a configuration of an entity exists.
     */
    public function hasConfig(): bool;

    /**
     * Gets a configuration of an entity.
     */
    public function getConfig(): ?EntityDefinitionConfig;

    /**
     * Sets a configuration of an entity.
     */
    public function setConfig(?EntityDefinitionConfig $definition): void;

    /**
     * Checks whether a configuration of filters for an entity exists.
     */
    public function hasConfigOfFilters(): bool;

    /**
     * Gets a configuration of filters for an entity.
     */
    public function getConfigOfFilters(): ?FiltersConfig;

    /**
     * Sets a configuration of filters for an entity.
     */
    public function setConfigOfFilters(?FiltersConfig $config): void;

    /**
     * Checks whether a configuration of sorters for an entity exists.
     */
    public function hasConfigOfSorters(): bool;

    /**
     * Gets a configuration of sorters for an entity.
     */
    public function getConfigOfSorters(): ?SortersConfig;

    /**
     * Sets a configuration of sorters for an entity.
     */
    public function setConfigOfSorters(?SortersConfig $config): void;

    /**
     * Checks whether a configuration of the given section exists.
     */
    public function hasConfigOf(string $configSection): bool;

    /**
     * Gets a configuration from the given section.
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function getConfigOf(string $configSection): mixed;

    /**
     * Sets a configuration for the given section.
     *
     * @throws \InvalidArgumentException if undefined configuration section is specified
     */
    public function setConfigOf(string $configSection, mixed $config): void;

    /**
     * Gets a list of requests for additional metadata info.
     *
     * @return MetadataExtraInterface[]
     */
    public function getMetadataExtras(): array;

    /**
     * Sets a list of requests for additional metadata info.
     *
     * @param MetadataExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setMetadataExtras(array $extras): void;

    /**
     * Checks whether some additional metadata info is requested.
     */
    public function hasMetadataExtra(string $extraName): bool;

    /**
     * Gets a request for some additional metadata info by its name.
     */
    public function getMetadataExtra(string $extraName): ?MetadataExtraInterface;

    /**
     * Adds a request for some additional metadata info.
     *
     * @throws \InvalidArgumentException if a metadata extra with the same name already exists
     */
    public function addMetadataExtra(MetadataExtraInterface $extra): void;

    /**
     * Removes a request for some additional metadata info.
     */
    public function removeMetadataExtra(string $extraName): void;

    /**
     * Checks whether metadata of an entity exists.
     */
    public function hasMetadata(): bool;

    /**
     * Gets metadata of an entity.
     */
    public function getMetadata(): ?EntityMetadata;

    /**
     * Sets metadata of an entity.
     */
    public function setMetadata(?EntityMetadata $metadata): void;
}
