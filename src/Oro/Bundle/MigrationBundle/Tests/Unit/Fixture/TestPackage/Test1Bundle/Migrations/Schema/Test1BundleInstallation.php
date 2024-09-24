<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class Test1BundleInstallation implements Installation
{
    #[\Override]
    public function getMigrationVersion()
    {
        return "v1_0";
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery('CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)');
    }
}
