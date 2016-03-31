<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @method bool has($key)
 * @method mixed get($key)
 * @method void set($key, $value)
 */
trait FormContextTrait
{
    /**
     * Returns request data.
     *
     * @return array|null
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
     * Gets a form builder.
     *
     * @return FormBuilderInterface|null
     */
    public function getFormBuilder()
    {
        return $this->get(FormContext::FORM);
    }

    /**
     * Sets a form builder.
     *
     * @param FormBuilderInterface|null $formBuilder
     */
    public function setFormBuilder(FormBuilderInterface $formBuilder = null)
    {
        $this->set(FormContext::FORM, $formBuilder);
    }

    /**
     * Gets a form.
     *
     * @return FormInterface|null
     */
    public function getForm()
    {
        return $this->get(FormContext::FORM);
    }

    /**
     * Sets a form.
     *
     * @param FormInterface|null $form
     */
    public function setForm(FormInterface $form = null)
    {
        $this->set(FormContext::FORM, $form);
    }
}
