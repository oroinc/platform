<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

class EmailNotificationEntityChoiceType extends EntityChoiceType
{
    public const NAME = 'oro_email_notification_entity_choice';

    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
