<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroSearchBundle implements Migration , ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        //save old avatars to new place
        /** @var EntityManager $em */
        $em         = $this->container->get('doctrine.orm.entity_manager');

        $driverName = $em->getConnection()->getDriver()->getName() ;

        if(in_array($driverName , array( 'pdo_mysql' ,'mysqli' ))) {

            $query      = "show variables like \"version\"";
            $version = $em->getConnection()->executeQuery($query)->fetchColumn(1);

            if(version_compare($version , '5.6.0' , '>=')) {
                $queries->addQuery('ALTER TABLE `oro_search_index_text` ENGINE = INNODB;');
            }
        }

    }
}
