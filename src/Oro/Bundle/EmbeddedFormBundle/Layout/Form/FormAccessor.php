<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Symfony\Component\Form\FormInterface;

/**
 * Provides access to a Symfony form and its metadata for layout rendering.
 *
 * This class wraps a Symfony FormInterface and provides convenient access to form properties
 * such as action, method, and enctype. It maintains a hash of the form configuration for identification purposes
 * and extends {@see AbstractFormAccessor} to inherit common form accessor functionality.
 */
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
    public function __construct(FormInterface $form, ?FormAction $action = null, $method = null, $enctype = null)
    {
        $this->form    = $form;
        $this->action  = $action;
        $this->method  = $method;
        $this->enctype = $enctype;

        $this->hash = $this->buildHash($this->form->getName(), $action, $method, $enctype);
    }

    #[\Override]
    public function getForm()
    {
        return $this->form;
    }

    #[\Override]
    public function toString()
    {
        return 'name:' . $this->getName();
    }

    #[\Override]
    public function getHash()
    {
        return $this->hash;
    }
}
