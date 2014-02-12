<?php

namespace Oro\Bundle\CalendarBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroCalendarBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_calendar (id INT AUTO_INCREMENT NOT NULL, user_owner_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, INDEX IDX_1D171519EB185F9 (user_owner_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_calendar_connection (id INT AUTO_INCREMENT NOT NULL, connected_calendar_id INT NOT NULL, calendar_id INT NOT NULL, created DATETIME NOT NULL, color VARCHAR(6) DEFAULT NULL, background_color VARCHAR(6) DEFAULT NULL, UNIQUE INDEX oro_calendar_connection_uq (calendar_id, connected_calendar_id), INDEX IDX_25D13AB8A40A2C8 (calendar_id), INDEX IDX_25D13AB8F94143E3 (connected_calendar_id), PRIMARY KEY(id));",
            "CREATE TABLE oro_calendar_event (id INT AUTO_INCREMENT NOT NULL, calendar_id INT NOT NULL, title LONGTEXT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, all_day TINYINT(1) NOT NULL, reminder TINYINT(1) NOT NULL, remind_at DATETIME DEFAULT NULL, reminded TINYINT(1) DEFAULT '0' NOT NULL, INDEX IDX_2DDC40DDA40A2C8 (calendar_id), INDEX oro_calendar_event_idx (calendar_id, start_at, end_at), PRIMARY KEY(id))",

            "ALTER TABLE oro_calendar ADD CONSTRAINT FK_1D171519EB185F9 FOREIGN KEY (user_owner_id) REFERENCES oro_user (id) ON DELETE SET NULL",
            "ALTER TABLE oro_calendar_connection ADD CONSTRAINT FK_25D13AB8F94143E3 FOREIGN KEY (connected_calendar_id) REFERENCES oro_calendar (id) ON DELETE CASCADE",
            "ALTER TABLE oro_calendar_connection ADD CONSTRAINT FK_25D13AB8A40A2C8 FOREIGN KEY (calendar_id) REFERENCES oro_calendar (id) ON DELETE CASCADE",
            "ALTER TABLE oro_calendar_event ADD CONSTRAINT FK_2DDC40DDA40A2C8 FOREIGN KEY (calendar_id) REFERENCES oro_calendar (id) ON DELETE CASCADE"
        ];
    }
}
