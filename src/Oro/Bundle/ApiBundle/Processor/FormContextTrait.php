<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Collection\AdditionalEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Provides the implementation for methods from {@see FormContext} interface.
 */
trait FormContextTrait
{
    private mixed $requestId = null;
    private array $requestData = [];
    private bool $existing = false;
    private ?array $includedData = null;
    private ?IncludedEntityCollection $includedEntities = null;
    private ?AdditionalEntityCollection $additionalEntities = null;
    private ?EntityMapper $entityMapper = null;
    private ?FormBuilderInterface $formBuilder = null;
    private ?FormInterface $form = null;
    private bool $skipFormValidation = false;
    private ?array $formOptions = null;
    /** @var ConfigExtraInterface[]|null $normalizedEntityConfigExtras */
    private ?array $normalizedEntityConfigExtras = null;
    /** @var ConfigExtraInterface[]|null $responseConfigExtras [name => extra, ...] */
    private ?array $responseConfigExtras = null;
    private EntityDefinitionConfig|null|false $normalizedConfig = false;
    private EntityMetadata|null|false $normalizedMetadata = false;

    /**
     * Gets an identifier of an entity that was sent in the request.
     */
    public function getRequestId(): mixed
    {
        return $this->requestId;
    }

    /**
     * Sets an identifier of an entity that was sent in the request.
     */
    public function setRequestId(mixed $requestId): void
    {
        $this->requestId = $requestId;
    }

    /**
     * Returns request data.
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * Sets request data to the context.
     */
    public function setRequestData(array $requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * Gets a value indicates whether an existing entity should be updated or new one should be created.
     */
    public function isExisting(): bool
    {
        return $this->existing;
    }

    /**
     * Sets a value indicates whether an existing entity should be updated or new one should be created.
     */
    public function setExisting(bool $existing): void
    {
        $this->existing = $existing;
    }

    /**
     * Returns additional data included into the request.
     */
    public function getIncludedData(): ?array
    {
        return $this->includedData;
    }

    /**
     * Sets additional data included into the request.
     */
    public function setIncludedData(?array $includedData): void
    {
        $this->includedData = $includedData;
    }

    /**
     * Returns a collection contains additional entities included into the request data.
     */
    public function getIncludedEntities(): ?IncludedEntityCollection
    {
        return $this->includedEntities;
    }

    /**
     * Sets a collection contains additional entities included into the request data.
     */
    public function setIncludedEntities(?IncludedEntityCollection $includedEntities): void
    {
        $this->includedEntities = $includedEntities;
    }

    /**
     * Gets the list of additional entities involved to the request processing.
     *
     * @return object[]
     */
    public function getAdditionalEntities(): array
    {
        return $this->getAdditionalEntityCollection()->getEntities();
    }

    /**
     * Adds the entity to a list of additional entities involved to the request processing.
     * For example when an association is represented as a field,
     * a target entity of this association does not exist in the list of included entities
     * and need to be persisted manually, so, it should be added to the list of additional entities.
     */
    public function addAdditionalEntity(object $entity): void
    {
        $this->getAdditionalEntityCollection()->add($entity);
    }

    /**
     * Adds an entity to the list of additional entities involved to the request processing
     * when this entity should be removed from the database.
     */
    public function addAdditionalEntityToRemove(object $entity): void
    {
        $this->getAdditionalEntityCollection()->add($entity, true);
    }

    /**
     * Removes an entity from the list of additional entities involved to the request processing.
     */
    public function removeAdditionalEntity(object $entity): void
    {
        $this->getAdditionalEntityCollection()->remove($entity);
    }

    /**
     * Gets a collection contains the list of additional entities involved to the request processing.
     */
    public function getAdditionalEntityCollection(): AdditionalEntityCollection
    {
        if (null === $this->additionalEntities) {
            $this->additionalEntities = new AdditionalEntityCollection();
        }

        return $this->additionalEntities;
    }

    /**
     * Gets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function getEntityMapper(): ?EntityMapper
    {
        return $this->entityMapper;
    }

    /**
     * Sets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function setEntityMapper(?EntityMapper $entityMapper): void
    {
        $this->entityMapper = $entityMapper;
    }

    /**
     * Checks whether the form builder exists.
     */
    public function hasFormBuilder(): bool
    {
        return null !== $this->formBuilder;
    }

    /**
     * Gets the form builder.
     */
    public function getFormBuilder(): ?FormBuilderInterface
    {
        return $this->formBuilder;
    }

    /**
     * Sets the form builder.
     */
    public function setFormBuilder(?FormBuilderInterface $formBuilder): void
    {
        $this->formBuilder = $formBuilder;
    }

    /**
     * Checks whether the form exists.
     */
    public function hasForm(): bool
    {
        return null !== $this->form;
    }

    /**
     * Gets the form.
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * Sets the form.
     */
    public function setForm(?FormInterface $form): void
    {
        $this->form = $form;
    }

    /**
     * Indicates whether the validation of the form should be skipped or not.
     */
    public function isFormValidationSkipped(): bool
    {
        return $this->skipFormValidation;
    }

    /**
     * Sets a flag indicates whether the validation of the form should be skipped or not.
     */
    public function skipFormValidation(bool $skipFormValidation): void
    {
        $this->skipFormValidation = $skipFormValidation;
    }

    /**
     * Gets form options that should override the options from the entity configuration.
     */
    public function getFormOptions(): ?array
    {
        return $this->formOptions;
    }

    /**
     * Sets form options that should override the options from the entity configuration.
     */
    public function setFormOptions(?array $formOptions): void
    {
        $this->formOptions = $formOptions ?: null;
    }

    /**
     * Sets a list of requests for configuration data.
     *
     * @param ConfigExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setConfigExtras(array $extras): void
    {
        $processedExtras = [];
        foreach ($extras as $extra) {
            $processedExtra = $this->processConfigExtra($extra);
            if (null !== $processedExtra) {
                $processedExtras[] = $processedExtra;
            }
        }
        parent::setConfigExtras($processedExtras);
    }

    /**
     * Adds a request for some configuration data.
     *
     * @throws \InvalidArgumentException if a config extra with the same name already exists
     */
    public function addConfigExtra(ConfigExtraInterface $extra): void
    {
        $processedExtra = $this->processConfigExtra($extra);
        if (null !== $processedExtra) {
            parent::addConfigExtra($processedExtra);
        }
    }

    /**
     * Removes a request for some configuration data.
     */
    public function removeConfigExtra(string $extraName): void
    {
        if (
            ExpandRelatedEntitiesConfigExtra::NAME === $extraName
            || FilterFieldsConfigExtra::NAME === $extraName
            || MetaPropertiesConfigExtra::NAME === $extraName
        ) {
            unset($this->responseConfigExtras[$extraName]);
        }
        parent::removeConfigExtra($extraName);
    }

    /**
     * Gets config extras that should be used by {@see \Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity}
     * and {@see \Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedIncludedEntities} processors.
     *
     * @return ConfigExtraInterface[]
     */
    public function getNormalizedEntityConfigExtras(): array
    {
        $configExtras = $this->responseConfigExtras ?? [];
        if ($this->normalizedEntityConfigExtras) {
            foreach ($this->normalizedEntityConfigExtras as $configExtra) {
                if (!isset($configExtras[$configExtra->getName()])) {
                    $configExtras[$configExtra->getName()] = $configExtra;
                }
            }
        }

        return array_values($configExtras);
    }

    /**
     * Sets config extras that should be used by {@see \Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity}
     * and {@see \Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedIncludedEntities} processors.
     *
     * @param ConfigExtraInterface[] $extras
     */
    public function setNormalizedEntityConfigExtras(array $extras): void
    {
        $this->normalizedEntityConfigExtras = $extras;
    }

    /**
     * Gets a configuration of an entity that should be used to converts an entity to a result array
     * when it is done not by "get" API action, e.g. for subresources.
     */
    public function getNormalizedConfig(): ?EntityDefinitionConfig
    {
        if (false !== $this->normalizedConfig) {
            return $this->normalizedConfig;
        }

        if (!$this->responseConfigExtras) {
            return $this->getConfig();
        }

        $this->normalizedConfig = null;
        $entityClass = $this->getClassName();
        if ($entityClass) {
            $this->normalizedConfig = $this->loadEntityConfig($entityClass, $this->getNormalizedConfigExtras())
                ->getDefinition();
        }

        return $this->normalizedConfig;
    }

    /**
     * Sets a configuration of an entity that should be used to converts an entity to a result array
     * when it is done not by "get" API action, e.g. for subresources.
     */
    public function setNormalizedConfig(?EntityDefinitionConfig $definition): void
    {
        $this->normalizedConfig = $definition;
    }

    /**
     * Gets a metadata of an entity that should be used to build a result document
     * when it is done not by "get" API action, e.g. for subresources.
     */
    public function getNormalizedMetadata(): ?EntityMetadata
    {
        if (false !== $this->normalizedMetadata) {
            return $this->normalizedMetadata;
        }

        if (!$this->responseConfigExtras) {
            return $this->getMetadata();
        }

        $this->normalizedMetadata = null;
        $entityClass = $this->getClassName();
        if ($entityClass) {
            $config = $this->getNormalizedConfig();
            if (null !== $config) {
                $this->normalizedMetadata = $this->metadataProvider->getMetadata(
                    $entityClass,
                    $this->getVersion(),
                    $this->getRequestType(),
                    $config,
                    $this->getMetadataExtras()
                );
            }
        }

        return $this->normalizedMetadata;
    }

    /**
     * Sets metadata of an entity that should be used to build a result document
     * when it is done not by "get" API action, e.g. for subresources.
     */
    public function setNormalizedMetadata(?EntityMetadata $metadata): void
    {
        $this->normalizedMetadata = $metadata;
    }

    /**
     * Gets all entities, primary and included ones, that are processing by an action.
     *
     * @param bool $mainOnly Whether only main entity(ies) for this request
     *                       or all, primary and included entities should be returned
     *
     * @return object[]
     */
    public function getAllEntities(bool $mainOnly = false): array
    {
        $entity = $this->getResult();
        $entities = null !== $entity ? [$entity] : [];
        if (!$mainOnly) {
            $includedEntities = $this->getIncludedEntities();
            if (null !== $includedEntities) {
                $entities = array_merge($entities, $includedEntities->getAll());
            }
        }

        return $entities;
    }

    private function processConfigExtra(ConfigExtraInterface $extra): ?ConfigExtraInterface
    {
        if ($extra instanceof ExpandRelatedEntitiesConfigExtra) {
            $this->responseConfigExtras[$extra->getName()] = $extra;

            return new ExpandRelatedEntitiesConfigExtra([]);
        }

        if ($extra instanceof FilterFieldsConfigExtra) {
            $this->responseConfigExtras[$extra->getName()] = $extra;

            return new FilterFieldsConfigExtra(array_fill_keys(array_keys($extra->getFieldFilters()), null));
        }

        if ($extra instanceof MetaPropertiesConfigExtra) {
            $this->responseConfigExtras[$extra->getName()] = $extra;

            return null;
        }

        return $extra;
    }

    /**
     * @return ConfigExtraInterface[]
     */
    private function getNormalizedConfigExtras(): array
    {
        $normalizedConfigExtras = [];
        $configExtras = $this->getConfigExtras();
        foreach ($configExtras as $configExtra) {
            $normalizedConfigExtras[$configExtra->getName()] = $configExtra;
        }
        foreach ($this->responseConfigExtras as $configExtra) {
            $normalizedConfigExtras[$configExtra->getName()] = $configExtra;
        }

        return array_values($normalizedConfigExtras);
    }
}
