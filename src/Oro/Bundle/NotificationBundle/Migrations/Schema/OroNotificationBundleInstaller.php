<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Installation;
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        OroNotificationBundle::oroNotificationEmailSpoolTable($schema);
        OroNotificationBundle::oroNotificationEmailnotificationTable($schema, 'oro_notification_emailnotif');
        OroNotificationBundle::oroNotificationEventTable($schema);
        OroNotificationBundle::oroNotificationRecipientGroupTable($schema, 'oro_notification_recipient_grp');
        OroNotificationBundle::oroNotificationRecipientListTable($schema, 'oro_notification_recipient_lst');
        OroNotificationBundle::oroNotificationRecipientUserTable($schema, 'oro_notification_recipient_usr');

        OroNotificationBundle::oroNotificationEmailnotificationForeignKeys(
            $schema,
            'oro_notification_emailnotif',
            'oro_notification_recipient_lst'
        );
        OroNotificationBundle::oroNotificationRecipientGroupForeignKeys(
            $schema,
            'oro_notification_recipient_grp',
            'oro_notification_recipient_lst'
        );
        OroNotificationBundle::oroNotificationRecipientUserForeignKeys(
            $schema,
            'oro_notification_recipient_usr',
            'oro_notification_recipient_lst'
        );

        return [];
    }
}
