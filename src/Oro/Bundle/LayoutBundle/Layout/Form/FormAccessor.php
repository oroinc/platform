<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\Form\FormInterface;

class FormAccessor extends AbstractFormAccessor
{
    /** @var FormInterface */
    protected $form;

    /** @var string */
    protected $hash;

    /**
     * @param FormInterface   $form    The form
     * @param FormAction|null $action  The submit action of the form
     * @param string|null     $method  The submit method of the form
     * @param string|null     $enctype The encryption type of the form
     */
    public function __construct(FormInterface $form, FormAction $action = null, $method = null, $enctype = null)
    {
        $this->form    = $form;
        $this->action  = $action;
        $this->method  = $method;
        $this->enctype = $enctype;

        $this->hash = $this->buildHash($this->form->getName(), $action, $method, $enctype);
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return $this->hash;
    }
}
