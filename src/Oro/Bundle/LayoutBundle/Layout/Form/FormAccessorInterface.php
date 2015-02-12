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
}
