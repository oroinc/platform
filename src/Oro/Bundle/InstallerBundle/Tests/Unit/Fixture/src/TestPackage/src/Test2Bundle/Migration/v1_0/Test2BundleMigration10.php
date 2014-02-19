<?php

namespace Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\InstallerBundle\Migrations\Migration;

class Test2BundleMigration10 implements Migration, ContainerAwareInterface
{
    protected $container;

    public function up(Schema $schema)
    {
        return $this->container->get('test_service')->getQueries();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
