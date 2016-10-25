<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

trait FormContextTrait
{
    /** @var array */
    protected $requestData;

    /** @var FormBuilderInterface|null */
    protected $formBuilder;

    /** @var FormInterface|null */
    protected $form;

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
     * Sets request data to the Context.
     *
     * @param array $requestData
     */
    public function setRequestData(array $requestData)
    {
        $this->requestData = $requestData;
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
}
