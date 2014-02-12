<?php

namespace Oro\Bundle\NotificationBundle\Migration\v1_0;

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
            "CREATE TABLE oro_notification_email_spool (id INT AUTO_INCREMENT NOT NULL, status INT NOT NULL, message LONGTEXT NOT NULL, INDEX notification_spool_status_idx (status), PRIMARY KEY(id))",
            "CREATE TABLE oro_notification_emailnotification (id INT AUTO_INCREMENT NOT NULL, recipient_list_id INT DEFAULT NULL, template_id INT DEFAULT NULL, event_id INT DEFAULT NULL, entity_name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F3D05A52B9E3E89 (recipient_list_id), INDEX IDX_F3D05A571F7E88B (event_id), INDEX IDX_F3D05A55DA0FB8 (template_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_notification_event (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_2E2482DF5E237E06 (name), PRIMARY KEY(id))",
            "CREATE TABLE oro_notification_recipient_group (recipient_list_id INT NOT NULL, group_id SMALLINT NOT NULL, INDEX IDX_F6E3D23E2B9E3E89 (recipient_list_id), INDEX IDX_F6E3D23EFE54D947 (group_id), PRIMARY KEY(recipient_list_id, group_id))",
            "CREATE TABLE oro_notification_recipient_list (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) DEFAULT NULL, owner TINYINT(1) DEFAULT NULL, PRIMARY KEY(id))",
            "CREATE TABLE oro_notification_recipient_user (recipient_list_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_CAC79D892B9E3E89 (recipient_list_id), INDEX IDX_CAC79D89A76ED395 (user_id), PRIMARY KEY(recipient_list_id, user_id))",

            "ALTER TABLE oro_notification_emailnotification ADD CONSTRAINT FK_F3D05A52B9E3E89 FOREIGN KEY (recipient_list_id) REFERENCES oro_notification_recipient_list (id)",
            "ALTER TABLE oro_notification_emailnotification ADD CONSTRAINT FK_F3D05A55DA0FB8 FOREIGN KEY (template_id) REFERENCES oro_email_template (id) ON DELETE SET NULL",
            "ALTER TABLE oro_notification_emailnotification ADD CONSTRAINT FK_F3D05A571F7E88B FOREIGN KEY (event_id) REFERENCES oro_notification_event (id)",
            "ALTER TABLE oro_notification_recipient_group ADD CONSTRAINT FK_F6E3D23EFE54D947 FOREIGN KEY (group_id) REFERENCES oro_access_group (id) ON DELETE CASCADE",
            "ALTER TABLE oro_notification_recipient_group ADD CONSTRAINT FK_F6E3D23E2B9E3E89 FOREIGN KEY (recipient_list_id) REFERENCES oro_notification_recipient_list (id) ON DELETE CASCADE",
            "ALTER TABLE oro_notification_recipient_user ADD CONSTRAINT FK_CAC79D89A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE",
            "ALTER TABLE oro_notification_recipient_user ADD CONSTRAINT FK_CAC79D892B9E3E89 FOREIGN KEY (recipient_list_id) REFERENCES oro_notification_recipient_list (id) ON DELETE CASCADE"
        ];
    }
}
