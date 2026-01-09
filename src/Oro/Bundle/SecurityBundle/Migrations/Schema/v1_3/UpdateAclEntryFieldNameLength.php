<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class UpdateAclEntryFieldNameLength implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->getTable($this->container->getParameter('security.acl.dbal.entry_table_name'))
            ->getColumn('field_name')
            ->setLength(255);
    }
}
