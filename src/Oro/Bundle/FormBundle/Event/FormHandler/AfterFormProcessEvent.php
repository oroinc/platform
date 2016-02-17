<?php

namespace Oro\Bundle\FormBundle\Event\FormHandler;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

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
    public function getForm()
    {
        return $this->form;
    }
}
