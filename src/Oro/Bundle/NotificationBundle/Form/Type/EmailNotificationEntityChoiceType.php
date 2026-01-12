<?php

namespace Oro\Bundle\NotificationBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

/**
 * Form type for selecting entities in email notification configurations.
 *
 * This form type extends the base {@see EntityChoiceType} to provide a specialized choice field
 * for selecting entities that should trigger email notifications. It is used in email
 * notification rule forms to allow administrators to specify which entity types should
 * be monitored for notification events. The type provides a consistent block prefix for
 * form rendering and template integration.
 */
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
