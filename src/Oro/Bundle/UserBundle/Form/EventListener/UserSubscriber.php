<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

class UserSubscriber implements EventSubscriberInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var SecurityContextInterface
     */
    protected $security;


    /**
     * @param FormFactoryInterface      $factory        Factory to add new form children
     * @param SecurityContextInterface  $security       Security context
     */
    public function __construct(
        FormFactoryInterface $factory,
        SecurityContextInterface $security
    ) {
        $this->factory = $factory;
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $submittedData = $event->getData();

        if (isset($submittedData['emails'])) {
            foreach ($submittedData['emails'] as $id => $email) {
                if (!$email['email']) {
                    unset($submittedData['emails'][$id]);
                }

            }
        }

        $event->setData($submittedData);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var User $user */
        $user = $event->getData();
        $businessUnits = $user->getBusinessUnits();
        $user->getOrganizations()->clear();
        foreach ($businessUnits as $businessUnit) {
            $organization = $businessUnit->getOrganization();
            $user->addOrganization($organization);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /* @var AbstractUser $entity */
        $entity = $event->getData();
        $form = $event->getForm();

        if (is_null($entity)) {
            return;
        }

        if ($entity->getId()) {
            $form->remove('plainPassword');
        }

        // do not allow user to disable his own account
        $form->add(
            $this->factory->createNamed(
                'enabled',
                'choice',
                $entity->getId() ? $entity->isEnabled() : '',
                [
                    'label' => 'Status',
                    'required' => true,
                    'disabled' => $this->isCurrentUser($entity),
                    'choices' => ['Inactive', 'Active'],
                    'empty_value' => 'Please select',
                    'empty_data' => '',
                    'auto_initialize' => false
                ]
            )
        );

        if (!$this->isCurrentUser($entity)) {
            $form->remove('change_password');
        }
    }

    /**
     * Returns true if passed user is currently authenticated
     *
     * @param  AbstractUser $user
     * @return bool
     */
    protected function isCurrentUser(AbstractUser $user)
    {
        $token = $this->security->getToken();
        $currentUser = $token ? $token->getUser() : null;
        if ($user->getId() && is_object($currentUser)) {
            return $currentUser->getId() == $user->getId();
        }

        return false;
    }
}
