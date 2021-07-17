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
interface FormContext extends ContextInterface
{
    /**
     * Returns request data.
     *
     * @return array
     */
    public function getRequestData();

    /**
     * Sets request data.
     */
    public function setRequestData(array $requestData);

    /**
     * Returns additional data included into the request.
     *
     * @return array
     */
    public function getIncludedData();

    /**
     * Sets additional data included into the request.
     */
    public function setIncludedData(array $includedData);

    /**
     * Returns a collection contains additional entities included into the request.
     *
     * @return IncludedEntityCollection|null
     */
    public function getIncludedEntities();

    /**
     * Sets a collection contains additional entities included into the request.
     */
    public function setIncludedEntities(IncludedEntityCollection $includedEntities = null);

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
     *
     * @param object $entity
     */
    public function addAdditionalEntity($entity): void;

    /**
     * Removes an entity from the list of additional entities involved to the request processing.
     *
     * @param object $entity
     */
    public function removeAdditionalEntity($entity): void;

    /**
     * Gets a service that can be used to convert an entity object to a model object and vise versa.
     *
     * @return EntityMapper|null
     */
    public function getEntityMapper();

    /**
     * Sets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function setEntityMapper(EntityMapper $entityMapper = null);

    /**
     * Checks whether the form builder exists.
     *
     * @return bool
     */
    public function hasFormBuilder();

    /**
     * Gets the form builder.
     *
     * @return FormBuilderInterface|null
     */
    public function getFormBuilder();

    /**
     * Sets the form builder.
     */
    public function setFormBuilder(FormBuilderInterface $formBuilder = null);

    /**
     * Checks whether the form exists.
     *
     * @return bool
     */
    public function hasForm();

    /**
     * Gets the form.
     *
     * @return FormInterface|null
     */
    public function getForm();

    /**
     * Sets the form.
     */
    public function setForm(FormInterface $form = null);

    /**
     * Indicates whether the validation of the form should be skipped or not.
     *
     * @return bool
     */
    public function isFormValidationSkipped();

    /**
     * Sets a flag indicates whether the validation of the form should be skipped or not.
     *
     * @param bool $skipFormValidation
     */
    public function skipFormValidation($skipFormValidation);
}
