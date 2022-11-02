<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

/**
 * Embedded form accessor for layouts that knows about a form data
 */
interface ConfigurableFormAccessorInterface extends FormAccessorInterface
{
    /**
     * Sets the form data.
     *
     * @param mixed $data
     */
    public function setFormData($data);
}
