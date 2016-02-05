<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSecurityBundle implements Migration, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateAclEntriesMigrationQuery(
                $this->container->get('oro_security.acl.manager'),
                $this->container->get('security.acl.cache'),
                $this->container->getParameter('security.acl.dbal.entry_table_name'),
                $this->container->getParameter('security.acl.dbal.oid_table_name'),
                $this->container->getParameter('security.acl.dbal.class_table_name')
            )
        );
    }
}
