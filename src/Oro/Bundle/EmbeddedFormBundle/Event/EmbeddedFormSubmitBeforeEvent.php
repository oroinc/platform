<?php

namespace Oro\Bundle\EmbeddedFormBundle\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

use Symfony\Component\EventDispatcher\Event;

class EmbeddedFormSubmitBeforeEvent extends Event
{
    const EVENT_NAME = 'oro_embedded_form.form_submit.before';

    /** @var  Object */
    protected $data;

    /** @var  EmbeddedForm */
    protected $formEntity;

    public function __construct($data, EmbeddedForm $formEntity)
    {
        $this->data = $data;
        $this->formEntity = $formEntity;
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
}
