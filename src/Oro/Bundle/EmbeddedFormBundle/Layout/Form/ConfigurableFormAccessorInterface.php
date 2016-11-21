<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

interface ConfigurableFormAccessorInterface extends FormAccessorInterface
{
    /**
     * Sets the form data.
     *
     * @param mixed
     */
    public function setFormData($data);
}
