<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\Form\Form;

interface FormContext extends ContextInterface
{
    /** A form for entity update process */
    const FORM = 'form';

    /** Request data that should be set to entity */
    const REQUEST_DATA = 'request_data';

    /**
     * Returns request data.
     *
     * @return array|null
     */
    public function getRequestData();

    /**
     * Sets request data to the Context.
     *
     * @param array $requestData
     */
    public function setRequestData(array $requestData);

    /**
     * Checks if form was set to the Context.
     *
     * @return bool
     */
    public function hasForm();

    /**
     * Gets a form.
     *
     * @return Form
     */
    public function getForm();

    /**
     * Sets a form.
     *
     * @param Form $form
     */
    public function setForm(Form $form);
}
