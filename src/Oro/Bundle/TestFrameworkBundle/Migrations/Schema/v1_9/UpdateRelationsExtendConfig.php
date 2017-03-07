<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateRelationsExtendConfig implements MigrationQuery, ConnectionAwareInterface
{
    /** @var string */
    private $entityName = 'Extend\Entity\TestEntity1';

    /** @var Connection */
    private $connection;

    /** @var array */
    private $fieldNames = [
        'biO2MNDTargets',
        'biO2MTargets',
        'uniO2MNDTargets',
        'uniO2MTargets',
        'biM2MNDTargets',
        'biM2MTargets',
        'uniM2MNDTargets',
        'uniM2MTargets',
        'biM2OTarget',
        'uniM2OTarget'
    ];

    /** @var array */
    private $extendedFields = [
        'target_title' => ['id'],
        'target_detailed' => ['id'],
        'target_grid' => ['id']
    ];

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update relation\'s extend config.';
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
    public function execute(LoggerInterface $logger)
    {
        $qb = $this->connection->createQueryBuilder()
            ->select(['orf.id', 'orf.data'])
            ->from('oro_entity_config_field', 'orf')
            ->innerJoin('orf', 'oro_entity_config', 'orc', 'orc.id = orf.entity_id')
            ->andWhere('orc.class_name = :class_name')
            ->where($this->connection->getExpressionBuilder()->in('orf.field_name', ':field_names'))
            ->setParameter('class_name', $this->entityName, \PDO::PARAM_STR)
            ->setParameter('field_names', $this->fieldNames, Connection::PARAM_STR_ARRAY);

        $allConfigFields = $qb->execute()->fetchAll();

        foreach ($allConfigFields as $configField) {
            $dataArray = $this->connection->convertToPHPValue($configField['data'], Type::TARRAY);
            $extendedFields = array_merge($this->extendedFields, $dataArray['extend']);
            $dataArray['extend'] = $extendedFields;
            $dataInDatabaseFormat = $this->connection->convertToDatabaseValue($dataArray, Type::TARRAY);

            $query = $this->connection->createQueryBuilder()
                ->update('oro_entity_config_field', 'c')
                ->set('data', ':data')
                ->where('c.id = :config_field_id')
                ->setParameter('config_field_id', $configField['id'])
                ->setParameter('data', $dataInDatabaseFormat, \PDO::PARAM_STR);
            $query->execute();
        }
    }
}
