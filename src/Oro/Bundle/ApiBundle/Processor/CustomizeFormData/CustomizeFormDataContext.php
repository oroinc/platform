<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormInterface;

/**
 * The execution context for processors for "customize_form_data" action.
 * Also {@see \Oro\Bundle\ApiBundle\Form\FormUtil} provides static methods that cam be helpful.
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
     * Note that this event is dispatched even if submitted data are not valid.
     * Use isValid() method of the form if your logic should be executed only if submitted data are valid.
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

    /** @var EntityMapper|null */
    private $entityMapper;

    /**
     * Checks if the context is already initialized.
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
        $this->setFirstGroup($event);
        $this->setLastGroup($event);
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
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }

    /**
     * Gets a form object related to the given entity.
     *
     * @param object $entity
     *
     * @return FormInterface|null
     */
    public function findForm($entity): ?FormInterface
    {
        if ($this->form->getData() === $entity) {
            return $this->form;
        }

        if (null === $this->includedEntities) {
            return null;
        }

        $data = $this->includedEntities->getData($entity);
        if (null === $data) {
            return null;
        }

        return $data->getForm();
    }

    /**
     * Finds a form field by its property path.
     *
     * @param string             $propertyPath The name of an entity field
     * @param FormInterface|null $form         The parent form of the searching child form
     *
     * @return FormInterface|null
     */
    public function findFormField(string $propertyPath, FormInterface $form = null): ?FormInterface
    {
        return FormUtil::findFormFieldByPropertyPath($form ?? $this->getForm(), $propertyPath);
    }

    /**
     * Finds the name of a form field by its property path.
     *
     * @param string             $propertyPath The name of an entity field
     * @param FormInterface|null $form         The parent form of the searching child form
     *
     * @return string|null
     */
    public function findFormFieldName(string $propertyPath, FormInterface $form = null): ?string
    {
        $fieldForm = $this->findFormField($propertyPath, $form);
        if (null === $fieldForm) {
            return null;
        }

        return $fieldForm->getName();
    }

    /**
     * Sets a form object related to a customizing entity.
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
     */
    public function getIncludedEntities(): ?IncludedEntityCollection
    {
        return $this->includedEntities;
    }

    /**
     * Sets a collection contains additional entities included into the request.
     */
    public function setIncludedEntities(IncludedEntityCollection $includedEntities): void
    {
        $this->includedEntities = $includedEntities;
    }

    /**
     * Indicates whether the current action is executed for the primary entity or for an included entity.
     */
    public function isPrimaryEntityRequest(): bool
    {
        $includedEntities = $this->getIncludedEntities();

        return
            null === $includedEntities
            || $includedEntities->getPrimaryEntity() === $this->getData();
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

    /**
     * Gets a service that can be used to convert an entity object to a model object and vise versa.
     *
     * @return EntityMapper|null
     */
    public function getEntityMapper()
    {
        return $this->entityMapper;
    }

    /**
     * Sets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function setEntityMapper(EntityMapper $entityMapper = null)
    {
        $this->entityMapper = $entityMapper;
    }
}
