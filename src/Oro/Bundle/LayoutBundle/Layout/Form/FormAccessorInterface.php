<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

interface FormAccessorInterface
{
    /**
     * Returns the form.
     *
     * @return FormInterface
     */
    public function getForm();

    /**
     * Returns the form view.
     *
     * @param string|null $fieldPath The path to the form field
     *                               If not specified a view for the root form is returned
     *
     * @return FormView
     */
    public function getView($fieldPath = null);

    /**
     * Returns all form fields for which blocks were created.
     *
     * @return string[] key = form field path, value = block id
     */
    public function getProcessedFields();

    /**
     * Sets form fields with corresponding blocks.
     *
     * @param string[] $processedFields key = form field path, value = block id
     *
     * @return string[] key = form field path, value = block id
     */
    public function setProcessedFields($processedFields);
}
