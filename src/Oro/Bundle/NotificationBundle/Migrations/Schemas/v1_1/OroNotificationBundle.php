<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schemas\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroNotificationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "RENAME TABLE oro_notification_emailnotification TO oro_notification_emailnotif;",
            "RENAME TABLE oro_notification_recipient_list TO oro_notification_recipient_lst;",
            "RENAME TABLE oro_notification_recipient_user TO oro_notification_recipient_usr;",
            "RENAME TABLE oro_notification_recipient_group TO oro_notification_recipient_grp;",
        ];
    }
}
