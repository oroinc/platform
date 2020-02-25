<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UpdateAclEntriesMigration implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateAclEntriesMigrationQuery(
                $this->container->get('oro_security.configuration.provider.permission_configuration'),
                $this->container->get('security.acl.cache'),
                $this->container->getParameter('security.acl.dbal.entry_table_name'),
                $this->container->getParameter('security.acl.dbal.oid_table_name'),
                $this->container->getParameter('security.acl.dbal.class_table_name')
            )
        );
    }
}
