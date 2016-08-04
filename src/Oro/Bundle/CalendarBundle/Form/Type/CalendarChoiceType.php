<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;

class CalendarChoiceType extends AbstractType
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param CalendarEventManager $calendarEventManager
     * @param TranslatorInterface  $translator
     */
    public function __construct(CalendarEventManager $calendarEventManager, TranslatorInterface $translator)
    {
        $this->calendarEventManager = $calendarEventManager;
        $this->translator           = $translator;
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
                    return $this->getChoices($options['is_new']);
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

        list($calendarAlias, $calendarId) = $this->calendarEventManager->parseCalendarUid($data);
        $this->calendarEventManager->setCalendar($parentData, $calendarAlias, $calendarId);
    }

    /**
     *M-BM- {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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

    /**
     * @param bool $isNew
     *
     * @return array key = calendarUid, value = calendar name
     */
    protected function getChoices($isNew)
    {
        $calendars = $this->calendarEventManager->getSystemCalendars();
        if ($isNew && count($calendars) === 1) {
            $calendars[0]['name'] = $this->translator->trans(
                'oro.calendar.add_to_calendar',
                ['%name%' => $calendars[0]['name']]
            );
        } elseif (!$isNew || count($calendars) !== 0) {
            usort(
                $calendars,
                function ($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                }
            );
            $userCalendars = $this->calendarEventManager->getUserCalendars();
            foreach ($userCalendars as $userCalendar) {
                $userCalendar['alias'] = Calendar::CALENDAR_ALIAS;
                array_unshift($calendars, $userCalendar);
            }
        }

        $choices = [];
        foreach ($calendars as $calendar) {
            $alias                 = !empty($calendar['alias'])
                ? $calendar['alias']
                : ($calendar['public'] ? SystemCalendar::PUBLIC_CALENDAR_ALIAS : SystemCalendar::CALENDAR_ALIAS);
            $calendarUid           = $this->calendarEventManager->getCalendarUid($alias, $calendar['id']);
            $choices[$calendarUid] = $calendar['name'];
        }

        return $choices;
    }
}
