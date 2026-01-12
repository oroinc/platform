<?php

namespace Oro\Bundle\FormBundle\Event\FormHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched during form processing to allow listeners to intervene.
 *
 * This event is triggered at key points in the form processing lifecycle, such as
 * before form data is set or before form submission. Listeners can interrupt the
 * form processing by calling {@see FormProcessEvent::interruptFormProcess()}, preventing
 * further processing and allowing for custom handling or validation logic.
 */
class FormProcessEvent extends Event implements FormAwareInterface
{
    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var bool
     */
    protected $formProcessInterrupted = false;

    /**
     * @param FormInterface $form
     * @param mixed         $data
     */
    public function __construct(FormInterface $form, $data)
    {
        $this->form = $form;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return FormInterface
     */
    #[\Override]
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return FormProcessEvent
     */
    public function interruptFormProcess()
    {
        $this->formProcessInterrupted = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFormProcessInterrupted()
    {
        return $this->formProcessInterrupted;
    }
}
