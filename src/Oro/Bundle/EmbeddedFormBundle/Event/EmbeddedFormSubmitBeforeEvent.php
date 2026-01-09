<?php

namespace Oro\Bundle\EmbeddedFormBundle\Event;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before an embedded form is submitted.
 *
 * This event is triggered before form validation and submission processing begins.
 * It provides access to the submitted data and the embedded form entity, allowing listeners
 * to perform pre-submission actions such as data validation, transformation, or modification
 * before the form is processed.
 */
class EmbeddedFormSubmitBeforeEvent extends Event
{
    public const EVENT_NAME = 'oro_embedded_form.form_submit.before';

    /** @var  Object */
    protected $data;

    /** @var  EmbeddedForm */
    protected $formEntity;

    public function __construct($data, EmbeddedForm $formEntity)
    {
        $this->data       = $data;
        $this->formEntity = $formEntity;
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
}
