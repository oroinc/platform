<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Test2BundleMigration10 implements Migration, ContainerAwareInterface, OrderedMigrationInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getOrder()
    {
        return 1;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $sqls = $this->container->get('test_service')->getQueries();
        foreach ($sqls as $sql) {
            $queries->addQuery($sql);
        }
    }
}
