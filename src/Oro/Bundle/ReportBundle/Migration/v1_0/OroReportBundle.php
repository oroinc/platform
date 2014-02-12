<?php

namespace Oro\Bundle\ReportBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroReportBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_report (id INT AUTO_INCREMENT NOT NULL, business_unit_owner_id INT DEFAULT NULL, type VARCHAR(32) DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, entity VARCHAR(255) NOT NULL, definition LONGTEXT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_B48821B68CDE5729 (type), INDEX IDX_B48821B659294170 (business_unit_owner_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_report_type (name VARCHAR(32) NOT NULL, `label` VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_397D3359EA750E8 (`label`), PRIMARY KEY(name))",

            "ALTER TABLE oro_report ADD CONSTRAINT FK_B48821B659294170 FOREIGN KEY (business_unit_owner_id) REFERENCES oro_business_unit (id) ON DELETE SET NULL",
            "ALTER TABLE oro_report ADD CONSTRAINT FK_B48821B68CDE5729 FOREIGN KEY (type) REFERENCES oro_report_type (name)"
        ];
    }
}
