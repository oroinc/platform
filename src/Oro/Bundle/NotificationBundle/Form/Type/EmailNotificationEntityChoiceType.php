<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

class EmailNotificationEntityChoiceType extends EntityChoiceType
{
    const NAME = 'oro_email_notification_entity_choice';

    /**
     * {@inheritdoc}
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
        return self::NAME;
    }
}
