<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @method bool has($key)
 * @method mixed get($key)
 * @method void set($key, $value)
 * @method void remove($key)
 */
trait FormContextTrait
{
    /**
     * Returns request data.
     *
     * @return array
     */
    public function getRequestData()
    {
        return $this->get(FormContext::REQUEST_DATA);
    }

    /**
     * Sets request data to the Context.
     *
     * @param array $requestData
     */
    public function setRequestData(array $requestData)
    {
        $this->set(FormContext::REQUEST_DATA, $requestData);
    }

    /**
     * Checks whether the form builder exists.
     *
     * @return bool
     */
    public function hasFormBuilder()
    {
        return $this->has(FormContext::FORM_BUILDER);
    }

    /**
     * Gets the form builder.
     *
     * @return FormBuilderInterface|null
     */
    public function getFormBuilder()
    {
        return $this->get(FormContext::FORM_BUILDER);
    }

    /**
     * Sets the form builder.
     *
     * @param FormBuilderInterface|null $formBuilder
     */
    public function setFormBuilder(FormBuilderInterface $formBuilder = null)
    {
        if ($formBuilder) {
            $this->set(FormContext::FORM_BUILDER, $formBuilder);
        } else {
            $this->remove(FormContext::FORM_BUILDER);
        }
    }

    /**
     * Checks whether the form exists.
     *
     * @return bool
     */
    public function hasForm()
    {
        return $this->has(FormContext::FORM);
    }

    /**
     * Gets the form.
     *
     * @return FormInterface|null
     */
    public function getForm()
    {
        return $this->get(FormContext::FORM);
    }

    /**
     * Sets the form.
     *
     * @param FormInterface|null $form
     */
    public function setForm(FormInterface $form = null)
    {
        if ($form) {
            $this->set(FormContext::FORM, $form);
        } else {
            $this->remove(FormContext::FORM);
        }
    }
}
