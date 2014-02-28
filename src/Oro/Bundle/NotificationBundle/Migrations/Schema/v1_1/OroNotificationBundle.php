<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class OroNotificationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "ALTER TABLE oro_notification_emailnotification RENAME TO oro_notification_email_notif;",
            "ALTER TABLE oro_notification_recipient_list RENAME TO oro_notification_recip_list;",
            "ALTER TABLE oro_notification_recipient_user RENAME TO oro_notification_recip_user;",
            "ALTER TABLE oro_notification_recipient_group RENAME TO oro_notification_recip_group;",
        ];
    }
}
