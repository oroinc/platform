<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\DefaultOwnerTypeAwareInterface;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitSelectType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Form\Type\OrganizationUserAclSelectType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultOwnerSubscriber implements EventSubscriberInterface
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param TypesRegistry          $typesRegistry
     */
    public function __construct(TokenAccessorInterface $tokenAccessor, TypesRegistry $typesRegistry)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->typesRegistry = $typesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSet',
            FormEvents::PRE_SET_DATA  => 'preSet',
            FormEvents::PRE_SUBMIT    => 'preSubmit',
        ];
    }

    /**
     * Sets default data for create integrations form
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data && !$data->getId() && !$data->getDefaultUserOwner() || null === $data) {
            if ($form->has('defaultUserOwner')) {
                $form->get('defaultUserOwner')->setData($this->tokenAccessor->getUser());
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        $this->setDefaultOwnerField($event->getForm(), $data->getType());
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if ($data === null || !isset($data['type'])) {
            return;
        }

        $this->setDefaultOwnerField($event->getForm(), $data['type']);
    }

    /**
     * Picks proper default owner field for form, based on type.
     *
     * @param FormInterface $form
     * @param string        $type
     */
    protected function setDefaultOwnerField(FormInterface $form, $type = null)
    {
        $type = $this->typesRegistry->getDefaultOwnerType($type);

        if ($type === DefaultOwnerTypeAwareInterface::BUSINESS_UNIT) {
            $this->addBusinessUnitField($form);
        } else {
            $this->addUserOwnerField($form);
        }
    }

    /**
     * Adds business unit default owner field to form.
     *
     * @param FormInterface $form
     */
    protected function addUserOwnerField(FormInterface $form)
    {
        if ($form->has('defaultBusinessUnitOwner')) {
            $form->remove('defaultBusinessUnitOwner');
        }
        if (!$form->has('defaultUserOwner')) {
            $form->add(
                'defaultUserOwner',
                OrganizationUserAclSelectType::class,
                [
                    'required' => true,
                    'label'    => 'oro.integration.integration.default_user_owner.label',
                    'tooltip'  => 'oro.integration.integration.default_user_owner.description',
                    'constraints' => new NotBlank(),
                ]
            );
        }
    }

    /**
     * Adds user default owner field to form.
     *
     * @param FormInterface $form
     */
    protected function addBusinessUnitField(FormInterface $form)
    {
        if ($form->has('defaultUserOwner')) {
            $form->remove('defaultUserOwner');
        }
        if (!$form->has('defaultBusinessUnitOwner')) {
            $form->add(
                'defaultBusinessUnitOwner',
                BusinessUnitSelectType::class,
                [
                    'required'    => true,
                    'label'       => 'oro.integration.integration.default_business_unit_owner.label',
                    'tooltip'     => 'oro.integration.integration.default_business_unit_owner.description',
                    'constraints' => new NotBlank(),
                ]
            );
        }
    }
}
