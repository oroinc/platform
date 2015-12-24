<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Acl\Voter\FieldVote;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AclProtectedFieldTypeExtension extends AbstractTypeExtension
{
    /** whether remove restricted fields */
    const IS_REMOVE_RESTRICTED = true;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $allowedFields = [];

    public function __construct(
        SecurityFacade $securityFacade,
        EntityClassResolver $entityClassResolver,
        DoctrineHelper $doctrineHelper
    ) {
        $this->securityFacade  = $securityFacade;
        $this->entityClassResolver = $entityClassResolver;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected function isApplicable(array $options)
    {
        $className = empty($options['data_class']) ? false : $options['data_class'];
        if (!$className || !$this->entityClassResolver->isEntity($className)) {
            // apply extension only to forms that bound to entities
            // cause there's no way to get object identifier for non-entity (can be any field, or even without it)

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        // Filter submitted data and ignore data for restricted fields
        if (static::IS_REMOVE_RESTRICTED) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        } else {
            // Check for restricted fields and add error messages if any
            $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateForbiddenFields']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        foreach ($form as $childName => $childForm) {
            if ($this->isFormGranted($childForm)) {
                continue;
            }

            $view->children[$childName]->setRendered();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (empty($data)) {
            return;
        }

        $form = $event->getForm();
        foreach ($form->all() as $childForm) {
            if ($this->isFormGranted($childForm)) {
                continue;
            }

            $form->remove($childForm->getName());
        }
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
            if ($this->isFormGranted($childForm)) {
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
    protected function isFormGranted(FormInterface $form)
    {
        $mainForm  = $form->getParent();
        $isMapped  = $form->getConfig()->getMapped();
        $className = $mainForm->getConfig()->getDataClass();
        $entity    = $mainForm->getData();

        if (false === $isMapped || !$entity instanceof $className) {
            $isGranted = true;
        } else {
            $isNewEntity = is_null($this->doctrineHelper->getSingleEntityIdentifier($entity));

            $isGranted = $this->securityFacade->isGranted(
                $isNewEntity ? 'CREATE' : 'EDIT',
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
