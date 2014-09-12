<?php

namespace Oro\Bundle\EmbeddedFormBundle\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class EmbeddedFormSubmitAfterEvent extends Event
{
    const EVENT_NAME = 'oro_embedded_form.form_submit.after';

    /** @var  Object */
    protected $data;

    /** @var  EmbeddedForm */
    protected $formEntity;

    /** @var  FormInterface */
    protected $form;

    public function __construct($data, EmbeddedForm $formEntity, FormInterface $form)
    {
        $this->data       = $data;
        $this->formEntity = $formEntity;
        $this->form       = $form;
    }

    /**
     * @return Object
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return EmbeddedForm
     */
    public function getFormEntity()
    {
        return $this->formEntity;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }
}
