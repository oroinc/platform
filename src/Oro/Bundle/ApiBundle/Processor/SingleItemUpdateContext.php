<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\Form\Form;

class SingleItemUpdateContext extends SingleItemContext
{
    /** A form for entity update process */
    const FORM = 'form';

    /** Request data that should be set to entity */
    const REQUEST_DATA = 'request_data';

    /**
     * Gets a form.
     *
     * @return Form
     */
    public function getForm()
    {
        return $this->get(self::FORM);
    }

    /**
     * Sets a form.
     *
     * @param Form $form
     */
    public function setForm(Form $form)
    {
        $this->set(self::FORM, $form);
    }

    /**
     * Checks if form was set to the Context.
     *
     * @return bool
     */
    public function hasForm()
    {
        return $this->has(self::FORM);
    }

    /**
     * Sets request data to the Context.
     *
     * @param array $requestData
     */
    public function setRequestData(array $requestData)
    {
        $this->set(self::REQUEST_DATA, $requestData);
    }

    /**
     * Returns request data.
     *
     * @return array|null
     */
    public function getRequestData()
    {
        return $this->get(self::REQUEST_DATA);
    }
}
