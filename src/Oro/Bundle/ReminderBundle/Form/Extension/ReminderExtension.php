<?php

namespace Oro\Bundle\ReminderBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Saves reminders on post submit event if a form work with an entity that implements RemindableInterface.
 */
class ReminderExtension extends AbstractTypeExtension implements ServiceSubscriberInterface
{
    use FormExtendedTypeTrait;

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_reminder.entity.manager' => ReminderManager::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit'], -128);
    }

    /**
     * Set form data
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $form->getData();

        if (!$form->isValid() || !$entity instanceof RemindableInterface || !$form->has('reminders')) {
            return;
        }

        /** @var ReminderManager $reminderManager */
        $reminderManager = $this->container->get('oro_reminder.entity.manager');
        $reminderManager->saveReminders($entity);
    }
}
