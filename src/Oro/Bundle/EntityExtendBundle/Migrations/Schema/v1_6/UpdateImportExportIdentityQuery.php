<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateImportExportIdentityQuery implements MigrationQuery, ConnectionAwareInterface
{
    /** @var Connection */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Allows to import/export enums by name';
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
        $this->updateField('id', false);
        $this->updateField('name', true);
    }

    /**
     * @param string $name
     * @param bool $identity
     */
    protected function updateField($name, $identity)
    {
        $fields = $this->createConfigFieldQb($name)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);

        $arrayType = Type::getType(Type::TARRAY);
        $updatedFields = array_map(
            function (array $row) use ($arrayType, $identity) {
                $data = $arrayType
                    ->convertToPHPValue($row['data'], $this->connection->getDatabasePlatform());

                if (!isset($data['importexport'])) {
                    $data['importexport'] = [];
                }
                $data['importexport']['identity'] = $identity;
                $row['data'] = $data;

                return $row;
            },
            $fields
        );

        array_walk(
            $updatedFields,
            function (array $field) {
                $this->connection->update(
                    'oro_entity_config_field',
                    ['data' => $field['data']],
                    ['id' => $field['id']],
                    [Type::TARRAY]
                );
            }
        );
    }

    /**
     * @param string $fieldName
     *
     * @return QueryBuilder
     */
    protected function createConfigFieldQb($fieldName)
    {
        return $this->connection->createQueryBuilder()
            ->select('ecf.id, ecf.data')
            ->from('oro_entity_config', 'ec')
            ->join('ec', 'oro_entity_config_field', 'ecf', 'ecf.entity_id = ec.id AND ecf.field_name = :field_name')
            ->where('ec.class_name LIKE :class_name')
            ->setParameters([
                'field_name' => $fieldName,
                'class_name' => 'Extend\\\\Entity\\\\EV_%',
            ]);
    }
}
