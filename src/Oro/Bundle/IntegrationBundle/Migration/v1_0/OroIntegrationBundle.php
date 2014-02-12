<?php

namespace Oro\Bundle\IntegrationBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroIntegrationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_integration_channel (id SMALLINT AUTO_INCREMENT NOT NULL, transport_id SMALLINT DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, connectors LONGTEXT NOT NULL COMMENT '(DC2Type:array)', UNIQUE INDEX UNIQ_55B9B9C59909C13F (transport_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_integration_channel_status (id SMALLINT AUTO_INCREMENT NOT NULL, channel_id SMALLINT NOT NULL, code VARCHAR(255) NOT NULL, connector VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, date DATETIME NOT NULL, INDEX IDX_C0D7E5FB72F5A1AA (channel_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_integration_transport (id SMALLINT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, wsdl_url VARCHAR(255) DEFAULT NULL, api_user VARCHAR(255) DEFAULT NULL, api_key VARCHAR(255) DEFAULT NULL, sync_start_date DATE DEFAULT NULL, sync_range VARCHAR(50) DEFAULT NULL, website_id INT DEFAULT NULL, websites LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', is_extension_installed TINYINT(1) DEFAULT NULL, is_wsi_mode TINYINT(1) DEFAULT NULL, PRIMARY KEY(id))",

            "ALTER TABLE oro_integration_channel ADD CONSTRAINT FK_55B9B9C59909C13F FOREIGN KEY (transport_id) REFERENCES oro_integration_transport (id)",
            "ALTER TABLE oro_integration_channel_status ADD CONSTRAINT FK_C0D7E5FB72F5A1AA FOREIGN KEY (channel_id) REFERENCES oro_integration_channel (id) ON DELETE CASCADE"
        ];
    }
}
