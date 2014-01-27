<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

class EmailNotificationEntityChoiceType extends EntityChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_email_notification_entity_choice';
    }
}
