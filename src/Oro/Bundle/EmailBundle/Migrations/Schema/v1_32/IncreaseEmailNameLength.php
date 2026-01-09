<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_32;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class IncreaseEmailNameLength implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->changeEmailFromNameColumnLength($schema);
        $this->changeEmailRecipientNameColumnLength($schema);
    }

    private function changeEmailFromNameColumnLength(Schema $schema): void
    {
        $table = $schema->getTable('oro_email');
        if ($table->getColumn('from_name')->getLength() < 320) {
            $table->modifyColumn('from_name', ['length' => 320]);
        }
    }

    private function changeEmailRecipientNameColumnLength(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_recipient');
        if ($table->getColumn('name')->getLength() < 320) {
            $table->modifyColumn('name', ['length' => 320]);
        }
    }
}
