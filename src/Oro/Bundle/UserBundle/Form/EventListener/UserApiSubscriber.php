<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Handles form events for user API operations.
 *
 * This event subscriber manages form field modifications for API user operations,
 * specifically handling the plainPassword field visibility based on whether the
 * user is being created or updated.
 */
class UserApiSubscriber implements EventSubscriberInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        if ($form->has('plainPassword')) {
            $form->remove('plainPassword');
        }

        if (!$data->getId()) {
            $form->add('plainPassword', PasswordType::class);
        }
    }
}
