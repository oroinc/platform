<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;

class CustomizeFormDataContext extends CustomizeDataContext
{
    /**
     * This event is dispatched at the beginning of the Form::submit() method.
     * @see Symfony\Component\Form\FormEvents::PRE_SUBMIT
     */
    const EVENT_PRE_SUBMIT = 'pre_submit';

    /**
     * This event is dispatched just before the Form::submit() method.
     * @see Symfony\Component\Form\FormEvents::SUBMIT
     */
    const EVENT_SUBMIT = 'submit';

    /**
     * This event is dispatched after the Form::submit() method, but before FormEvents::POST_SUBMIT.
     * @see Symfony\Component\Form\FormEvents::POST_SUBMIT
     */
    const EVENT_POST_SUBMIT = 'post_submit';

    /**
     * This event is dispatched after the Form::submit() method, but after FormEvents::POST_SUBMIT.
     * It can be used to finalize the form after all listeners, including data validation listener,
     * are executed. E.g. it can be used to correct form validation result.
     * @see Symfony\Component\Form\FormEvents::POST_SUBMIT
     */
    const EVENT_FINISH_SUBMIT = 'finish_submit';

    /** the form event name */
    const EVENT = 'event';

    /** @var FormInterface */
    protected $form;

    /** @var mixed */
    protected $data;

    /**
     * Gets the form event name.
     *
     * @return string One of "pre_submit", "submit" or "post_submit"
     */
    public function getEvent()
    {
        return $this->get(self::EVENT);
    }

    /**
     * Gets the form event name.
     *
     * @param string $event One of "pre_submit", "submit" or "post_submit"
     */
    public function setEvent($event)
    {
        $this->set(self::EVENT, $event);
    }

    /**
     * Gets a form object related to a customizing entity.
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Sets a form object related to a customizing entity.
     *
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * Gets the data associated with form event event.
     * For "pre_submit" event it is the submitted data.
     * For "submit" event it is the norm data.
     * For "post_submit" event it is the view data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the data associated with form event event.
     *
     * @param mixed $data
     */
    public function setData($data)
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
