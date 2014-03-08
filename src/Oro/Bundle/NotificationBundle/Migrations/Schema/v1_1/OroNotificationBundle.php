<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNotificationBundle extends Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addSql(
            $queries->getRenameTableSql('oro_notification_emailnotification', 'oro_notification_email_notif')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_notification_recipient_list', 'oro_notification_recip_list')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_notification_recipient_user', 'oro_notification_recip_user')
        );
        $queries->addSql(
            $queries->getRenameTableSql('oro_notification_recipient_group', 'oro_notification_recip_group')
        );
    }
}
