<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class OroNotificationBundle extends Migration implements RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            $this->renameExtension->getRenameTableQuery(
                'oro_notification_emailnotification',
                'oro_notification_email_notif'
            )
        );
        $queries->addQuery(
            $this->renameExtension->getRenameTableQuery(
                'oro_notification_recipient_list',
                'oro_notification_recip_list'
            )
        );
        $queries->addQuery(
            $this->renameExtension->getRenameTableQuery(
                'oro_notification_recipient_user',
                'oro_notification_recip_user'
            )
        );
        $queries->addQuery(
            $this->renameExtension->getRenameTableQuery(
                'oro_notification_recipient_group',
                'oro_notification_recip_group'
            )
        );
    }
}
