<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;

class CustomizeFormDataContext extends CustomizeDataContext
{
    /** @var FormInterface */
    protected $form;

    /**
     * Gets a form object related to a customizing entity.
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Sets a form object related to a customizing entity.
     *
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }
}
