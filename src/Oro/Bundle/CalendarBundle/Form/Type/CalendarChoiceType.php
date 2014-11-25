<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Manager\CalendarChoiceManager;

class CalendarChoiceType extends AbstractType
{
    /** @var CalendarChoiceManager */
    protected $calendarChoiceManager;

    /**
     * @param CalendarChoiceManager $calendarChoiceManager
     */
    public function __construct(CalendarChoiceManager $calendarChoiceManager)
    {
        $this->calendarChoiceManager = $calendarChoiceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmitData']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices'              => function (Options $options) {
                    return $this->calendarChoiceManager->getChoices($options['is_new']);
                },
                'is_new'               => false,
                'translatable_options' => false
            )
        );
        $resolver->setNormalizers(
            array(
                'expanded'    => function (Options $options, $expanded) {
                    return count($options['choices']) === 1;
                },
                'multiple'    => function (Options $options, $multiple) {
                    return count($options['choices']) === 1;
                },
                'empty_value' => function (Options $options, $emptyValue) {
                    return count($options['choices']) !== 1 ? null : null;
                },
            )
        );
    }

    /**
     * POST_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function postSubmitData(FormEvent $event)
    {
        $form = $event->getForm();

        $data = $form->getData();
        if (empty($data)) {
            return;
        }
        if (is_array($data)) {
            $data = reset($data);
        }

        /** @var CalendarEvent $parentData */
        $parentData = $form->getParent()->getData();
        if (!$parentData) {
            return;
        }

        list($calendarAlias, $calendarId) = $this->calendarChoiceManager->parseCalendarUid($data);
        $this->calendarChoiceManager->setCalendar($parentData, $calendarAlias, $calendarId);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
