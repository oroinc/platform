<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Collection\AdditionalEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\ChangeContextInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeDataContext;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\FormInterface;

/**
 * The execution context for processors for "customize_form_data" action.
 * Also {@see \Oro\Bundle\ApiBundle\Form\FormUtil} provides static methods that cam be helpful.
 */
class CustomizeFormDataContext extends CustomizeDataContext implements ChangeContextInterface
{
    /**
     * This event is dispatched at the beginning of the Form::submit() method.
     * @see \Symfony\Component\Form\FormEvents::PRE_SUBMIT
     */
    public const EVENT_PRE_SUBMIT = 'pre_submit';

    /**
     * This event is dispatched after the Form::submit() method has submitted and mapped the children,
     * and after reverse transformation to normalized representation.
     * @see \Symfony\Component\Form\FormEvents::SUBMIT
     */
    public const EVENT_SUBMIT = 'submit';

    /**
     * This event is dispatched at the very end of the Form::submit().
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

    /**
     * This event is dispatched after the database transaction is open but before data are flushed into the database.
     * Do not call EntityManager::persist() and EntityManager::remove() during handling of this event,
     * use {@see addAdditionalEntity()} and {@see addAdditionalEntityToRemove()} instead.
     * @see \Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandler
     */
    public const EVENT_PRE_FLUSH_DATA = 'pre_flush_data';

    /**
     * This event is dispatched after data are successfully flushed into the database
     * but before the database transaction is committed.
     * @see \Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandler
     */
    public const EVENT_POST_FLUSH_DATA = 'post_flush_data';

    /**
     * This event is dispatched after data are successfully flushed into the database,
     * and the database transaction is committed.
     * It can be used to perform some not crucial operations after data are saved into the database.
     * It means that failure of these operations will not roll back data saved into the database.
     * @see \Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandler
     */
    public const EVENT_POST_SAVE_DATA = 'post_save_data';

    /** the form event name */
    private const EVENT = 'event';

    private ?FormInterface $form = null;
    private mixed $data = null;
    private ?IncludedEntityCollection $includedEntities = null;
    private ?AdditionalEntityCollection $additionalEntities = null;
    private ?EntityMapper $entityMapper = null;

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
     * @return string One of EVENT_* constants
     */
    public function getEvent(): string
    {
        return $this->get(self::EVENT);
    }

    /**
     * Gets the form event name.
     *
     * @param string $event One of EVENT_* constants
     */
    public function setEvent(string $event): void
    {
        $this->set(self::EVENT, $event);
        $this->setFirstGroup($event);
        $this->setLastGroup($event);
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
     */
    public function findForm(object $entity): ?FormInterface
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
     */
    public function findFormField(string $propertyPath, ?FormInterface $form = null): ?FormInterface
    {
        return FormUtil::findFormFieldByPropertyPath($form ?? $this->getForm(), $propertyPath);
    }

    /**
     * Finds the name of a form field by its property path.
     */
    public function findFormFieldName(string $propertyPath, ?FormInterface $form = null): ?string
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
     * For other events it is the view data.
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Sets the data associated with the form event.
     */
    public function setData(mixed $data): void
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
        if (null !== $this->includedEntities) {
            throw new \LogicException('The collection of included entities was already initialized.');
        }
        $this->includedEntities = $includedEntities;
    }

    /**
     * Gets all entities, primary and included ones, that are processing by an action.
     *
     * @param bool $mainOnly Whether only main entity(ies) for this request
     *                       or all, primary and included entities should be returned
     *
     * @return object[]
     */
    #[\Override]
    public function getAllEntities(bool $mainOnly = false): array
    {
        $includedEntities = $this->getIncludedEntities();
        if ($mainOnly || null === $includedEntities) {
            $entity = $this->getForm()->getData();

            return null !== $entity ? [$entity] : [];
        }

        $entity = $includedEntities->getPrimaryEntity();

        return array_merge(null !== $entity ? [$entity] : [], $includedEntities->getAll());
    }

    /**
     * Indicates whether the current action is executed for the primary entity or for an included entity.
     */
    public function isPrimaryEntityRequest(): bool
    {
        $includedEntities = $this->getIncludedEntities();

        return
            null === $includedEntities
            || $includedEntities->getPrimaryEntity() === $this->getForm()->getData();
    }

    /**
     * Gets the list of additional entities involved to the request processing.
     *
     * @return object[]
     */
    public function getAdditionalEntities(): array
    {
        return $this->getAdditionalEntityCollection()->getEntities();
    }

    /**
     * Adds the entity to a list of additional entities involved to the request processing.
     * For example when an association is represented as a field,
     * a target entity of this association does not exist in the list of included entities
     * and need to be persisted manually, so, it should be added to the list of additional entities.
     */
    public function addAdditionalEntity(object $entity): void
    {
        $this->getAdditionalEntityCollection()->add($entity);
    }

    /**
     * Adds an entity to the list of additional entities involved to the request processing
     * when this entity should be removed from the database.
     */
    public function addAdditionalEntityToRemove(object $entity): void
    {
        $this->getAdditionalEntityCollection()->add($entity, true);
    }

    /**
     * Removes an entity from the list of additional entities involved to the request processing.
     */
    public function removeAdditionalEntity(object $entity): void
    {
        $this->getAdditionalEntityCollection()->remove($entity);
    }

    /**
     * Initializes the collection of additional entities.
     */
    public function setAdditionalEntityCollection(AdditionalEntityCollection $additionalEntities): void
    {
        if (null !== $this->additionalEntities) {
            throw new \LogicException('The collection of additional entities was already initialized.');
        }
        $this->additionalEntities = $additionalEntities;
    }

    /**
     * Gets a collection contains the list of additional entities involved to the request processing.
     */
    public function getAdditionalEntityCollection(): AdditionalEntityCollection
    {
        if (null === $this->additionalEntities) {
            throw new \LogicException('The collection of additional entities was not initialized.');
        }

        return $this->additionalEntities;
    }

    /**
     * Gets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function getEntityMapper(): ?EntityMapper
    {
        return $this->entityMapper;
    }

    /**
     * Sets a service that can be used to convert an entity object to a model object and vise versa.
     */
    public function setEntityMapper(?EntityMapper $entityMapper): void
    {
        $this->entityMapper = $entityMapper;
    }

    /**
     * This method is just an alias for getData.
     */
    #[\Override]
    public function getResult(): mixed
    {
        return $this->data;
    }

    /**
     * This method is just an alias for setData.
     */
    #[\Override]
    public function setResult(mixed $data): void
    {
        $this->data = $data;
    }

    #[\Override]
    public function hasResult(): bool
    {
        return true;
    }

    #[\Override]
    public function removeResult(): void
    {
        throw new \BadMethodCallException('Not implemented.');
    }
}
