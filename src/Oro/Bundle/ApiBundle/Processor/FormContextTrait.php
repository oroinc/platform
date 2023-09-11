<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Collection\AdditionalEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Provides the implementation for methods from {@see FormContext} interface.
 */
trait FormContextTrait
{
    private array $requestData = [];
    private bool $existing = false;
    private ?array $includedData = null;
    private ?IncludedEntityCollection $includedEntities = null;
    private ?AdditionalEntityCollection $additionalEntities = null;
    private ?EntityMapper $entityMapper = null;
    private ?FormBuilderInterface $formBuilder = null;
    private ?FormInterface $form = null;
    private bool $skipFormValidation = false;
    private ?array $normalizedEntityConfigExtras = null;

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
     * Gets config extras that should be used by {@see \Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity}
     * and {@see \Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedIncludedEntities} processors.
     *
     * @return ConfigExtraInterface[]
     */
    public function getNormalizedEntityConfigExtras(): array
    {
        return $this->normalizedEntityConfigExtras ?? [];
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
}
