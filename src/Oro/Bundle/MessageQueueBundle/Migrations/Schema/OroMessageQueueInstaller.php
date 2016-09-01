<?php
namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\MessageQueue\Job\Schema as UniqueJobSchema;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroMessageQueueBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createDbalQueueTable($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createDbalQueueTable(Schema $schema)
    {
        $queueSchema = new DbalSchema(
            $this->getDbalConnection(),
            'oro_message_queue'
        );

        $queueSchema->addToSchema($schema);
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getDbalConnection()
    {
        return $this->container->get('doctrine.dbal.default_connection');
    }
}
