<?php

namespace Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class Test2BundleMigration10 extends Migration implements ContainerAwareInterface
{
    protected $container;

    public function up(Schema $schema, QueryBag $queries)
    {
        return $this->container->get('test_service')->getQueries();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
