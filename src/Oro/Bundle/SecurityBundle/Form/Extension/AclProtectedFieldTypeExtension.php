<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AclProtectedFieldTypeExtension extends AbstractTypeExtension
{
    /** whether remove restricted field's data from submitted */
    const IS_REMOVE_RESTRICTED = false;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var array forbidden form field names */
    protected $forbiddenFields = [];

    public function __construct(SecurityFacade $securityFacade, EntityClassResolver $entityClassResolver)
    {
        $this->securityFacade  = $securityFacade;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $className = empty($options['data_class']) ? false : $options['data_class'];
        if (!$className || !$this->entityClassResolver->isEntity($className)) {
            // apply extension only to forms that bound to entities
            // cause there's no way to get object identifier for non-entity (can be any field, or even without it)

            return;
        }

        // Filter submitted data and ignore data for restricted fields
        if (static::IS_REMOVE_RESTRICTED) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        }

        // Check for restricted fields and add error messages if any
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateForbiddenFields']);
    }

    /**
     * Method could be safely removed if there's no need to cleanup submitted data
     *
     * {@inheritdoc}
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (empty($data)) {
            return;
        }

        $forbiddenFields = [];
        foreach ($event->getForm()->all() as $childForm) {
            if ($this->isGranted($childForm)) {
                continue;
            }

            $forbiddenFields[] = $childForm->getName();
        }

        // cache forbidden field names
        $this->forbiddenFields = array_flip($forbiddenFields);

        // remove restricted data from submitted data
        $data = array_diff_key($data, $this->forbiddenFields);
        $event->setData($data);
    }

    /**
     * Used on post submit to add validation errors
     *
     * @param FormEvent $event
     */
    public function validateForbiddenFields(FormEvent $event)
    {
        $entity = $event->getData();
        $className = $event->getForm()->getConfig()->getDataClass();
        if (!$entity instanceof $className) {
            return;
        }

        foreach ($event->getForm()->all() as $childForm) {
            if (static::IS_REMOVE_RESTRICTED) {
                $isGranted = !isset($this->forbiddenFields[$childForm->getName()]);
            } else {
                $isGranted = $this->isGranted($childForm);
            }

            if ($isGranted) {
                continue;
            }

            // add violation to form
            $childForm->addError(
                new FormError(
                    sprintf('You are not allowed to modify \'%s\' field.', $childForm->getName())
                    // do not use message template and 'message parameters' params here
                    // they are not processed in SOAP responses, only message will be used
                )
            );
        }
    }

    /**
     * Check if current session allowed to modify form
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isGranted(FormInterface $form)
    {
        $mainForm  = $form->getParent();
        $isMapped  = $form->getConfig()->getMapped();
        $className = $mainForm->getConfig()->getDataClass();
        $entity    = $mainForm->getData();

        if (false === $isMapped || !$entity instanceof $className) {
            $isGranted = true;
        } else {
            $isGranted = $this->securityFacade->isGranted(
                'EDIT',
                new FieldVote($entity, $this->getPropertyByForm($form))
            );
        }

        return $isGranted;
    }

    /**
     * Return class property form mapped to
     *
     * @param FormInterface $form
     *
     * @return string
     */
    protected function getPropertyByForm(FormInterface $form)
    {
        $propertyPath = $form->getConfig()->getPropertyPath();
        $isMapped  = $form->getConfig()->getMapped();

        return $isMapped && $propertyPath && $propertyPath->getLength() == 1 ? (string)$propertyPath : $form->getName();
    }
}
