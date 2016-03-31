<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\Form\Form;

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
     * Checks if form was set to the Context.
     *
     * @return bool
     */
    public function hasForm()
    {
        return $this->has(FormContext::FORM);
    }

    /**
     * Gets a form.
     *
     * @return Form
     */
    public function getForm()
    {
        return $this->get(FormContext::FORM);
    }

    /**
     * Sets a form.
     *
     * @param Form $form
     */
    public function setForm(Form $form)
    {
        $this->set(FormContext::FORM, $form);
    }
}
