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
        static::addTextBodyFieldToEmailBodyTable($schema);

        // send migration message to queue
        $this->container->get('oro_message_queue.message_producer')->send('testTopic', '');
    }

    /**
     * @param Schema $schema
     */
    public static function addTextBodyFieldToEmailBodyTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_body');
        if (!$table->hasColumn('text_body')) {
            $table->addColumn('text_body', 'text', ['notnull' => false]);
        }
    }
}
