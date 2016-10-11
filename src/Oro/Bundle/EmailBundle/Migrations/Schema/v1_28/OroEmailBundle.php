<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_28;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
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
        // We should not do anything if text_body field already exists.
        // This field could be added during update from old versions.
        if ($schema->getTable('oro_email_body')->hasColumn('text_body')) {
            return;
        }

        static::addTextBodyFieldToEmailBodyTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function addTextBodyFieldToEmailBodyTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_body');
        $table->addColumn('text_body', 'text', ['notnull' => false]);
    }
}
