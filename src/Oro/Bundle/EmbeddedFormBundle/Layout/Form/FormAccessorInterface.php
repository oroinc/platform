<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Oro\Component\Layout\ContextItemInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Defines the contract for accessing form data and metadata in layout contexts.
 *
 * This interface provides methods to retrieve form objects, views, and configuration
 * details such as action, method, and enctype. It also manages processed fields tracking,
 * allowing layout builders to keep track of which form fields have been rendered. Implementations
 * serve as adapters between Symfony forms and the layout system.
 */
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
