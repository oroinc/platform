<?php

namespace Oro\Bundle\FormBundle\Event\FormHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a form has been processed.
 *
 * This event is triggered after form submission and validation have completed,
 * allowing listeners to perform post-processing actions such as persisting data,
 * sending notifications, or triggering side effects. The event carries both the
 * processed form and the associated data.
 */
class AfterFormProcessEvent extends Event implements FormAwareInterface
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
}
