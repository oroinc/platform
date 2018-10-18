<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;

class UserSubscriber implements EventSubscriberInterface
{
    /** @var FormFactoryInterface */
    protected $factory;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param FormFactoryInterface   $factory       Factory to add new form children
     * @param TokenAccessorInterface $tokenAccessor Security token accessor
     */
    public function __construct(FormFactoryInterface $factory, TokenAccessorInterface $tokenAccessor)
    {
        $this->factory = $factory;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
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

        if (!$this->isCurrentUser($entity)) {
            $form->remove('change_password');
        }

        $enabledChoices = ['oro.user.enabled.disabled' => 0, 'oro.user.enabled.enabled' => 1];

        // do not allow editing of Enabled status
        if (!empty($entity->getId())) {
            $form->add('enabled', HiddenType::class, ['mapped' => false]);

            return;
        }

        $form->add(
            $this->factory->createNamed(
                'enabled',
                ChoiceType::class,
                '',
                [
                    'label' => 'oro.user.enabled.label',
                    'required' => true,
                    'disabled' => false,
                    'choices' => $enabledChoices,
                    'placeholder' => 'Please select',
                    'empty_data' => '',
                    'auto_initialize' => false
                ]
            )
        );
    }

    /**
     * Returns true if passed user is currently authenticated
     *
     * @param  AbstractUser $user
     *
     * @return bool
     */
    protected function isCurrentUser(AbstractUser $user)
    {
        $token = $this->tokenAccessor->getToken();
        $currentUser = $token ? $token->getUser() : null;
        if ($user->getId() && is_object($currentUser)) {
            return $currentUser->getId() == $user->getId();
        }

        return false;
    }
}
