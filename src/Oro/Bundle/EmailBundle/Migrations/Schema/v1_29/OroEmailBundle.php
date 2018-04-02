<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_29;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

        // send migration message to queue
        $this->container->get('oro_message_queue.message_producer')
            ->send(UpgradeEmailBodyMessageProcessor::TOPIC_NAME, '');
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
