<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class CalendarChoiceTemplateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_calendar_choice_template';
    }
}
