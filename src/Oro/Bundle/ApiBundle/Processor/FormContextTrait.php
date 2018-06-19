<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Provides the implementation for methods from FormContext interface.
 * @see \Oro\Bundle\ApiBundle\Processor\FormContext
 */
trait FormContextTrait
{
    /** @var array */
    private $requestData;

    /** @var array */
    private $includedData;

    /** @var IncludedEntityCollection|null */
    private $includedEntities;

    /** @var EntityMapper|null */
    private $entityMapper;

    /** @var FormBuilderInterface|null */
    private $formBuilder;

    /** @var FormInterface|null */
    private $form;

    /** @var bool */
    protected $skipFormValidation = false;

    /**
     * Returns request data.
     *
     * @return array
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * Sets request data to the context.
     *
     * @param array $requestData
     */
    public function setRequestData(array $requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * Returns additional data included into the request.
     *
     * @return array
     */
    public function getIncludedData()
    {
        return $this->includedData;
    }

    /**
     * Sets additional data included into the request.
     *
     * @param array $includedData
     */
    public function setIncludedData(array $includedData)
    {
        $this->includedData = $includedData;
    }

    /**
     * Returns a collection contains additional entities included into the request data.
     *
     * @return IncludedEntityCollection|null
     */
    public function getIncludedEntities()
    {
        return $this->includedEntities;
    }

    /**
     * Sets a collection contains additional entities included into the request data.
     *
     * @param IncludedEntityCollection|null $includedEntities
     */
    public function setIncludedEntities(IncludedEntityCollection $includedEntities = null)
    {
        $this->includedEntities = $includedEntities;
    }

    /**
     * Gets a service that can be used to convert an entity object to a model object and vise versa.
     *
     * @return EntityMapper|null
     */
    public function getEntityMapper()
    {
        return $this->entityMapper;
    }

    /**
     * Sets a service that can be used to convert an entity object to a model object and vise versa.
     *
     * @param EntityMapper|null $entityMapper
     */
    public function setEntityMapper(EntityMapper $entityMapper = null)
    {
        $this->entityMapper = $entityMapper;
    }

    /**
     * Checks whether the form builder exists.
     *
     * @return bool
     */
    public function hasFormBuilder()
    {
        return null !== $this->formBuilder;
    }

    /**
     * Gets the form builder.
     *
     * @return FormBuilderInterface|null
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }

    /**
     * Sets the form builder.
     *
     * @param FormBuilderInterface|null $formBuilder
     */
    public function setFormBuilder(FormBuilderInterface $formBuilder = null)
    {
        $this->formBuilder = $formBuilder;
    }

    /**
     * Checks whether the form exists.
     *
     * @return bool
     */
    public function hasForm()
    {
        return null !== $this->form;
    }

    /**
     * Gets the form.
     *
     * @return FormInterface|null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Sets the form.
     *
     * @param FormInterface|null $form
     */
    public function setForm(FormInterface $form = null)
    {
        $this->form = $form;
    }

    /**
     * Indicates whether the validation of the form should be skipped or not.
     *
     * @return bool
     */
    public function isFormValidationSkipped()
    {
        return $this->skipFormValidation;
    }

    /**
     * Sets a flag indicates whether the validation of the form should be skipped or not.
     *
     * @param bool $skipFormValidation
     */
    public function skipFormValidation($skipFormValidation)
    {
        $this->skipFormValidation = $skipFormValidation;
    }
}
