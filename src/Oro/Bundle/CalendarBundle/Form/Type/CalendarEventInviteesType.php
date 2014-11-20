<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CalendarBundle\Form\DataTransformer\EventsToUsersTransformer;

class CalendarEventInviteesType extends AbstractType
{
    const NAME = 'oro_calendar_event_invitees';

    /**
     * @var EventsToUsersTransformer
     */
    protected $eventsToUsersTransformer;

    /**
     * @param EventsToUsersTransformer $eventsToUsersTransformer
     */
    public function __construct(EventsToUsersTransformer $eventsToUsersTransformer)
    {
        $this->eventsToUsersTransformer = $eventsToUsersTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->eventsToUsersTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_user_multiselect';
    }
}
