<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Security\RememberMe\DoctrineTokenProvider;

/**
 * Decorate doctrine token storage that is set in “remember-me” cookies.
 */
class DoctrineTokenProviderDecorator extends DoctrineTokenProvider
{
    public function __construct(private Connection $connection)
    {
        parent::__construct($connection);
    }

    #[\Override]
    public function configureSchema(Schema $schema, Connection $forConnection): void
    {
        if ($forConnection !== $this->connection) {
            return;
        }
        if ($schema->hasTable('rememberme_token')) {
            return;
        }
        $this->addTableToSchema($schema);
    }

    private function addTableToSchema(Schema $schema): void
    {
        $table = $schema->createTable('rememberme_token');
        $table->addColumn('series', Types::STRING, ['fixed' => true, 'length' => 88]);
        $table->addColumn('value', Types::STRING, ['length' => 88]);
        $table->addColumn('lastUsed', Types::DATETIME_MUTABLE);
        $table->addColumn('class', Types::STRING, ['length' => 255]);
        $table->addColumn('username', Types::STRING, ['length' => 255]);
        $table->setPrimaryKey(['series']);
    }
}
