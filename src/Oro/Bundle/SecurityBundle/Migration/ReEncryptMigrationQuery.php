<?php

namespace Oro\Bundle\SecurityBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;

/**
 * Re-encrypt database fields using new encrypter
 *
 * It can be used to migrate from one implementation of SymmetricCrypterInterface to another
 */
class ReEncryptMigrationQuery implements MigrationQuery, ConnectionAwareInterface
{
    /** @var Connection */
    private $connection;

    /** @var SymmetricCrypterInterface */
    private $originalCrypter;

    /** @var SymmetricCrypterInterface */
    private $newCrypter;

    /** @var string */
    private $table;

    /** @var string[] */
    private $fields;

    public function __construct(
        SymmetricCrypterInterface $originalCrypter,
        SymmetricCrypterInterface $newCrypter,
        string $table,
        array $fields
    ) {
        $this->originalCrypter = $originalCrypter;
        $this->newCrypter = $newCrypter;
        $this->table = $table;
        $this->fields = $fields;
    }

    /**
     * {@inheritDoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return 'Re-encrypt database fields using new encrypter';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $select = implode(', ', array_merge(['id'], $this->fields));

        $selectIntegrationsQB = $this->connection->createQueryBuilder()
            ->select($select)
            ->from($this->table);

        foreach ($selectIntegrationsQB->execute()->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $updateData = [];
            foreach ($this->fields as $field) {
                if (isset($row[$field])) {
                    $decrypted = $this->originalCrypter->decryptData($row[$field]);
                    $updateData[$field] = $this->newCrypter->encryptData($decrypted);
                }
            }

            // skip row if no any updates required
            if (empty($updateData)) {
                continue;
            }

            $this->connection->update($this->table, $updateData, ['id' => $row['id']]);
        }
    }
}
