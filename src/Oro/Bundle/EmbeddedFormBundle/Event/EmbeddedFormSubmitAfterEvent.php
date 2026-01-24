<?php

namespace Oro\Bundle\EmbeddedFormBundle\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after an embedded form is successfully submitted.
 *
 * This event is triggered after form validation and submission processing is complete.
 * It provides access to the submitted data, the embedded form entity, and the form object
 * itself, allowing listeners to perform post-submission actions such as logging, notifications,
 * or additional data processing.
 */
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
