<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNotificationBundle implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_notification_emailnotification',
            'oro_notification_email_notif'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_notification_recipient_list',
            'oro_notification_recip_list'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_notification_recipient_user',
            'oro_notification_recip_user'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_notification_recipient_group',
            'oro_notification_recip_group'
        );
    }
}
