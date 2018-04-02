<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\InstallerBundle\Migration\RenameActivityAssociations20;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class RenameActivityAssociations implements
    Migration,
    RenameExtensionAwareInterface,
    ConnectionAwareInterface,
    NameGeneratorAwareInterface
{
    /** @var RenameExtension */
    private $renameExtension;

    /** @var Connection */
    private $connection;

    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $helper = new RenameActivityAssociations20(
            $this->connection,
            $this->nameGenerator,
            $this->renameExtension
        );
        $helper->rename($schema, $queries);
    }
}
