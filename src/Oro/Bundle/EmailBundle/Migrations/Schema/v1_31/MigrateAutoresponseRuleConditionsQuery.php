<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_31;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Log\LoggerInterface;

class MigrateAutoresponseRuleConditionsQuery implements MigrationQuery, ConnectionAwareInterface
{
    /** @var Connection */
    protected $connection;

    const LIMIT = 100;

    const AUTO_RESPONSE_RULE_TABLE = 'oro_email_auto_response_rule';
    const AUTO_RESPONSE_RULE_CONDITION_TABLE = 'oro_email_response_rule_cond';

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getAutoresponseRuleCount() / static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $this->createAutoResponseRuleConditionQb($i * static::LIMIT)
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            $grouppedRows = $this->groupRowsByRules($rows);
            foreach ($grouppedRows as $ruleId => $conditions) {
                $this->processGroup($ruleId, $conditions);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Migrates data from table "oro_email_response_rule_cond" into "oro_email_auto_response_rule"';
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param int $ruleId
     * @param array $conditions
     */
    protected function processGroup($ruleId, array $conditions)
    {
        $this->connection->update(
            static::AUTO_RESPONSE_RULE_TABLE,
            [
                'definition' => $this->createDefinition($conditions),
            ],
            [
                'id' => $ruleId,
            ],
            [
                Type::TEXT,
            ]
        );
    }

    /**
     * @param array $rows
     *
     * @return array
     */
    protected function groupRowsByRules(array $rows)
    {
        $groupped = [];
        foreach ($rows as $row) {
            $groupped[$row['rule_id']][] = $row;
        }

        return $groupped;
    }

    /**
     * @param array $conditions
     *
     * @return string
     */
    protected function createDefinition(array $conditions)
    {
        $filters = array_map(
            function ($condition) {
                return [
                    'columnName' => $condition['field'],
                    'criterion' => [
                        'filter' => 'string',
                        'data' => [
                            'value' => $condition['filterValue'],
                            'type'  => $condition['filterType'],
                        ],
                    ],
                ];
            },
            $conditions
        );

        return json_encode([
            'filters' =>  ArrayUtil::interpose('OR', $filters),
        ]);
    }

    /**
     * @param int $offset
     *
     * @return QueryBuilder
     */
    protected function createAutoResponseRuleConditionQb($offset)
    {
        $idsResult = $this->connection->createQueryBuilder()
            ->select('r.id')
            ->from(static::AUTO_RESPONSE_RULE_TABLE, 'r')
            ->setMaxResults(static::LIMIT)
            ->setFirstResult($offset)
            ->orderBy('r.id')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);

        $qb = $this->connection->createQueryBuilder();

        return $qb
            ->select('c.rule_id, c.field, c.filterType, c.filterValue')
            ->from(static::AUTO_RESPONSE_RULE_CONDITION_TABLE, 'c')
            ->andWhere($qb->expr()->in('c.rule_id', ':ids'))
            ->setParameter('ids', array_map('current', $idsResult), Connection::PARAM_INT_ARRAY)
            ->orderBy('c.position');
    }

    /**
     * @return int
     */
    protected function getAutoresponseRuleCount()
    {
        return $this->connection->createQueryBuilder()
            ->select('COUNT(1)')
            ->from(static::AUTO_RESPONSE_RULE_TABLE, 'r')
            ->execute()
            ->fetchColumn();
    }
}
