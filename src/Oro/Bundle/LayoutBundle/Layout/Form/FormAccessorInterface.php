<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Component\Layout\ContextItemInterface;

interface FormAccessorInterface extends ContextItemInterface
{
    /**
     * Returns the form.
     *
     * @return FormInterface
     */
    public function getForm();

    /**
     * Returns the name of the form.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the id of the form.
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the submit action of the form.
     *
     * @return FormAction
     */
    public function getAction();

    /**
     * Returns the submit method of the form.
     *
     * @return string|null
     */
    public function getMethod();

    /**
     * Returns the encryption type of the form.
     *
     * @return string|null
     */
    public function getEnctype();

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
