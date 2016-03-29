<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Symfony\Component\Form\Form;

class SingleItemUpdateContext extends SingleItemContext
{
    /** A form for entity update process */
    const FORM = 'form';

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
     * Checks if form was set to context.
     *
     * @return bool
     */
    public function hasForm()
    {
        return $this->has(self::FORM);
    }
}
