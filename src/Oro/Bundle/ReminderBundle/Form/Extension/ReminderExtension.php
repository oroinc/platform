<?php

namespace Oro\Bundle\ReminderBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;

class ReminderExtension extends AbstractTypeExtension
{
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
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            array($this, 'postSubmit')
        );
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

        if (!$entity instanceof RemindableInterface || !$form->has('reminders')) {
            return;
        }

        $this->manager->saveReminders($entity);
    }
}
