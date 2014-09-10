<?php

namespace Oro\Bundle\EmbeddedFormBundle\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\Form;

class EmbeddedFormSubmitAfterEvent extends Event
{
    const EVENT_NAME = 'oro_embedded_form.form_submit.after';

    /** @var  Object */
    protected $data;

    /** @var  EmbeddedForm */
    protected $formEntity;

    /** @var  Form */
    protected $form;

    public function __construct($data, EmbeddedForm $formEntity, $form)
    {
        $this->data = $data;
        $this->formEntity = $formEntity;
        $this->form = $form;
    }

    /**
     * @param Object $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return Object
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param EmbeddedForm $formEntity
     */
    public function setFormEntity($formEntity)
    {
        $this->formEntity = $formEntity;
    }

    /**
     * @return EmbeddedForm
     */
    public function getFormEntity()
    {
        return $this->formEntity;
    }

    /**
     * @param Form $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }
}
