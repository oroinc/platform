<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\NotificationBundle\Migrations\Schema\v1_0\OroNotificationBundle;

class OroNotificationBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        OroNotificationBundle::oroNotificationEmailSpoolTable($schema);
        OroNotificationBundle::oroNotificationEmailNotificationTable($schema, 'oro_notification_email_notif');
        OroNotificationBundle::oroNotificationEventTable($schema);
        OroNotificationBundle::oroNotificationRecipientGroupTable($schema, 'oro_notification_recip_group');
        OroNotificationBundle::oroNotificationRecipientListTable($schema, 'oro_notification_recip_list');
        OroNotificationBundle::oroNotificationRecipientUserTable($schema, 'oro_notification_recip_user');

        OroNotificationBundle::oroNotificationEmailNotificationForeignKeys(
            $schema,
            'oro_notification_email_notif',
            'oro_notification_recip_list'
        );
        OroNotificationBundle::oroNotificationRecipientGroupForeignKeys(
            $schema,
            'oro_notification_recip_group',
            'oro_notification_recip_list'
        );
        OroNotificationBundle::oroNotificationRecipientUserForeignKeys(
            $schema,
            'oro_notification_recip_user',
            'oro_notification_recip_list'
        );

        return [];
    }
}
