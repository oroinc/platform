<?php

namespace Oro\Bundle\OrganizationBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroOrganizationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, currency VARCHAR(3) NOT NULL, currency_precision VARCHAR(10) NOT NULL, PRIMARY KEY(id))",
            "CREATE TABLE oro_business_unit (id INT AUTO_INCREMENT NOT NULL, business_unit_owner_id INT DEFAULT NULL, organization_id INT NOT NULL, name VARCHAR(255) NOT NULL, phone VARCHAR(100) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, fax VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_C033B2D532C8A3DE (organization_id), INDEX IDX_C033B2D559294170 (business_unit_owner_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_business_unit ADD CONSTRAINT FK_C033B2D559294170 FOREIGN KEY (business_unit_owner_id) REFERENCES oro_business_unit (id) ON DELETE SET NULL",
            "ALTER TABLE oro_business_unit ADD CONSTRAINT FK_C033B2D532C8A3DE FOREIGN KEY (organization_id) REFERENCES oro_organization (id) ON DELETE CASCADE"
        ];
    }
}
