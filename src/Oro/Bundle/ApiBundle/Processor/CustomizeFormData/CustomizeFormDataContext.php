<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;
use Symfony\Component\Form\FormInterface;

/**
 * The execution context for processors for "customize_form_data" action.
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
     * This event is dispatched after the Form::submit() method.
     * @see \Symfony\Component\Form\FormEvents::POST_SUBMIT
     */
    public const EVENT_POST_SUBMIT = 'post_submit';

    /**
     * This event is dispatched at the end of the form submitting process, just before data validation.
     * It can be used to final form data correcting after all listeners, except data validation listener,
     * are executed and all relationships between submitted data are set.
     * @see \Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension
     * @see \Oro\Bundle\ApiBundle\Form\FormValidationHandler
     */
    public const EVENT_PRE_VALIDATE = 'pre_validate';

    /**
     * This event is dispatched at the end of the form submitting process, just after data validation.
     * It can be used to finalize the form after all listeners, including data validation listener,
     * are executed. E.g. it can be used to correct form validation result.
     * @see \Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension
     * @see \Oro\Bundle\ApiBundle\Form\FormValidationHandler
     */
    public const EVENT_POST_VALIDATE = 'post_validate';

    /** the form event name */
    private const EVENT = 'event';

    /** the name of the action which causes this action, e.g. "create" or "update" */
    private const PARENT_ACTION = 'parentAction';

    /** @var FormInterface */
    private $form;

    /** @var mixed */
    private $data;

    /** @var IncludedEntityCollection|null */
    private $includedEntities;

    /**
     * Checks if the context is already initialized.
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return null !== $this->form;
    }

    /**
     * Gets the form event name.
     *
     * @return string One of "pre_submit", "submit", "post_submit", "pre_validate" or "post_validate"
     */
    public function getEvent(): string
    {
        return $this->get(self::EVENT);
    }

    /**
     * Gets the form event name.
     *
     * @param string $event One of "pre_submit", "submit", "post_submit", "pre_validate" or "post_validate"
     */
    public function setEvent(string $event): void
    {
        $this->set(self::EVENT, $event);
    }

    /**
     * Gets the name of the action which causes this action, e.g. "create" or "update".
     *
     * @return string|null
     */
    public function getParentAction()
    {
        return $this->get(self::PARENT_ACTION);
    }

    /**
     * Sets the name of the action which causes this action, e.g. "create" or "update".
     *
     * @param string|null $action
     */
    public function setParentAction($action)
    {
        if ($action) {
            $this->set(self::PARENT_ACTION, $action);
        } else {
            $this->remove(self::PARENT_ACTION);
        }
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
     * For "post_submit", "pre_validate" and "post_validate" events it is the view data.
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
     * Returns a collection contains additional entities included into the request.
     *
     * @return IncludedEntityCollection|null
     */
    public function getIncludedEntities(): ?IncludedEntityCollection
    {
        return $this->includedEntities;
    }

    /**
     * Sets a collection contains additional entities included into the request.
     *
     * @param IncludedEntityCollection $includedEntities
     */
    public function setIncludedEntities(IncludedEntityCollection $includedEntities): void
    {
        $this->includedEntities = $includedEntities;
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
