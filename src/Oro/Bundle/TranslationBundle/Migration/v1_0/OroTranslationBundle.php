<?php

namespace Oro\Bundle\TranslationBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroTranslationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_translation (id INT AUTO_INCREMENT NOT NULL, value LONGTEXT DEFAULT NULL, locale VARCHAR(5) NOT NULL, domain VARCHAR(255) NOT NULL, `key` VARCHAR(500) NOT NULL, scope SMALLINT NOT NULL, INDEX MESSAGE_IDX (locale, domain, `key`, scope), PRIMARY KEY(id))",
        ];
    }
}
