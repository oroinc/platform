<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * An interface for form related execution contexts,
 * like contexts for such actions as "create", "update" and modify relationships.
 */
interface FormContext extends ContextInterface, ChangeContextInterface
{
    /**
     * Returns request data.
     */
    public function getRequestData(): array;

    /**
     * Sets request data.
     */
    public function setRequestData(array $requestData): void;

    /**
     * Returns additional data included into the request.
     */
    public function getIncludedData(): ?array;

    /**
     * Sets additional data included into the request.
     */
    public function setIncludedData(?array $includedData): void;

    /**
     * Returns a collection contains additional entities included into the request.
     */
    public function getIncludedEntities(): ?IncludedEntityCollection;

    /**
     * Sets a collection contains additional entities included into the request.
     */
    public function setIncludedEntities(?IncludedEntityCollection $includedEntities): void;

    /**
     * Gets the list of additional entities involved to the request processing.
     *
     * @return object[]
     */
    public function getAdditionalEntities(): array;

    /**
     * Adds an entity to the list of additional entities involved to the request processing.
     * For example when an association is represented as a field,
     * a target entity of this association does not exist in the list of included entities
     * and need to be persisted manually, so, it should be added to the list of additional entities.
     */
    public function addAdditionalEntity(object $entity): void;

    /**
     * Removes an entity from the list of additional entities involved to the request processing.
     */
    public function removeAdditionalEntity(object $entity): void;

    /**
     * Gets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function getEntityMapper(): ?EntityMapper;

    /**
     * Sets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function setEntityMapper(?EntityMapper $entityMapper): void;

    /**
     * Checks whether the form builder exists.
     */
    public function hasFormBuilder(): bool;

    /**
     * Gets the form builder.
     */
    public function getFormBuilder(): ?FormBuilderInterface;

    /**
     * Sets the form builder.
     */
    public function setFormBuilder(?FormBuilderInterface $formBuilder): void;

    /**
     * Checks whether the form exists.
     */
    public function hasForm(): bool;

    /**
     * Gets the form.
     */
    public function getForm(): ?FormInterface;

    /**
     * Sets the form.
     */
    public function setForm(?FormInterface $form): void;

    /**
     * Indicates whether the validation of the form should be skipped or not.
     */
    public function isFormValidationSkipped(): bool;

    /**
     * Sets a flag indicates whether the validation of the form should be skipped or not.
     */
    public function skipFormValidation(bool $skipFormValidation): void;
}
