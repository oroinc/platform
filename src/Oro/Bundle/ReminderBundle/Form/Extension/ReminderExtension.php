<?php

namespace Oro\Bundle\ReminderBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ReminderExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /**
     * @var ReminderManager
     */
    protected $manager;

    /**
     * @param ReminderManager $manager
     */
    public function __construct(ReminderManager $manager)
    {
        $this->manager = $manager;
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
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $form->getData();

        if (!$form->isValid() || !$entity instanceof RemindableInterface || !$form->has('reminders')) {
            return;
        }

        $this->manager->saveReminders($entity);
    }
}
