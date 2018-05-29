<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;
use Symfony\Component\Form\FormInterface;

/**
 * The context for the "customize_form_data" action.
 */
class CustomizeFormDataContext extends CustomizeDataContext
{
    /**
     * This event is dispatched at the beginning of the Form::submit() method.
     * @see \Symfony\Component\Form\FormEvents::PRE_SUBMIT
     */
    public const EVENT_PRE_SUBMIT = 'pre_submit';

    /**
     * This event is dispatched just before the Form::submit() method.
     * @see \Symfony\Component\Form\FormEvents::SUBMIT
     */
    public const EVENT_SUBMIT = 'submit';

    /**
     * This event is dispatched after the Form::submit() method, but before FormEvents::POST_SUBMIT.
     * @see \Symfony\Component\Form\FormEvents::POST_SUBMIT
     */
    public const EVENT_POST_SUBMIT = 'post_submit';

    /**
     * This event is dispatched at the end of the form submitting process.
     * It can be used to finalize the form after all listeners, including data validation listener,
     * are executed. E.g. it can be used to correct form validation result.
     * @see \Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension
     * @see \Oro\Bundle\ApiBundle\Form\FormValidationHandler
     */
    public const EVENT_FINISH_SUBMIT = 'finish_submit';

    /** the form event name */
    private const EVENT = 'event';

    /** @var FormInterface */
    private $form;

    /** @var mixed */
    private $data;

    /**
     * Gets the form event name.
     *
     * @return string One of "pre_submit", "submit", "post_submit" and "finish_submit"
     */
    public function getEvent(): string
    {
        return $this->get(self::EVENT);
    }

    /**
     * Gets the form event name.
     *
     * @param string $event One of "pre_submit", "submit", "post_submit" and "finish_submit"
     */
    public function setEvent(string $event): void
    {
        $this->set(self::EVENT, $event);
    }

    /**
     * Gets a form object related to a customizing entity.
     *
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * Sets a form object related to a customizing entity.
     *
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form): void
    {
        $this->form = $form;
    }

    /**
     * Gets the data associated with the form event.
     * For "pre_submit" event it is the submitted data.
     * For "submit" event it is the norm data.
     * For "post_submit" and "finish_submit" events it is the view data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the data associated with the form event.
     *
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * This method is just an alias for getData.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->data;
    }

    /**
     * This method is just an alias for setData.
     *
     * @param mixed $data
     */
    public function setResult($data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function hasResult()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeResult()
    {
        throw new \BadMethodCallException('Not implemented.');
    }
}
