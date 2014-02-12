<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroEmbeddedFormBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_embedded_form (id VARCHAR(255) NOT NULL, channel_id SMALLINT DEFAULT NULL, title LONGTEXT NOT NULL, css LONGTEXT NOT NULL, form_type VARCHAR(255) NOT NULL, success_message VARCHAR(255) NOT NULL, INDEX IDX_F7A34C172F5A1AA (channel_id), PRIMARY KEY(id))",
            "ALTER TABLE oro_embedded_form ADD CONSTRAINT FK_F7A34C172F5A1AA FOREIGN KEY (channel_id) REFERENCES oro_integration_channel (id)"
        ];
    }
}
