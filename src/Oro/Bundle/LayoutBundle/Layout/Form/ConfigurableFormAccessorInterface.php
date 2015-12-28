<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

interface ConfigurableFormAccessorInterface extends FormAccessorInterface
{
    /**
     * Sets the form data.
     *
     * @param mixed
     */
    public function setFormData($data);
}
