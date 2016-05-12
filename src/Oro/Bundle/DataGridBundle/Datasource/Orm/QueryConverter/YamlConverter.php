<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;
use Oro\Bundle\QueryDesignerBundle\Model\GroupByHelper;

class YamlConverter implements QueryConverterInterface
{
    const MAX_ITERATIONS = 100;

    /**
     * {@inheritdoc}
     */
    public function parse($value, ManagerRegistry $doctrine)
    {
        if (!is_array($value)) {
            $value = Yaml::parse(file_get_contents($value));
        }

        $processor = new Processor();

        $value = $processor->processConfiguration(new QueryConfiguration(), $value);

        if (!isset($value['from'])) {
            throw new InvalidConfigurationException('Missing mandatory "from" section');
        }

        $qb = $this->createQueryBuilder($doctrine, $value);
        foreach ((array)$value['from'] as $from) {
            $qb->from($from['table'], $from['alias']);
        }

        if (isset($value['select'])) {
            foreach ($value['select'] as $select) {
                $qb->add('select', new Expr\Select($select), true);
            }
        }

        if (isset($value['distinct'])) {
            $qb->distinct((bool)$value['distinct']);
        }

        $groupByFields = $this->getGroupByFields($value);
        if ($groupByFields) {
            $qb->groupBy(implode(',', $groupByFields));
        }

        if (isset($value['having'])) {
            $qb->having($value['having']);
        }

        $this->addJoin($qb, $value);
        $this->addWhere($qb, $value);
        $this->addOrder($qb, $value);

        return $qb;
    }

    /**
     * @param array $value
     * @return array
     */
    protected function getGroupByFields(array $value)
    {
        $groupByHelper = new GroupByHelper();

        if (isset($value['groupBy'])) {
            $groupBy = $value['groupBy'];
        } else {
            $groupBy = [];
        }

        if (isset($value['select'])) {
            $select = $value['select'];
        } else {
            $select = [];
        }

        return $groupByHelper->getGroupByFields($groupBy, $select);
    }

    /**
     * {@inheritdoc}
     */
    public function dump(QueryBuilder $input)
    {
        return '';
    }

    /**
     * @param ManagerRegistry $doctrine
     * @param array           $definition
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder(ManagerRegistry $doctrine, array $definition)
    {
        return $doctrine->getManagerForClass($definition['from'][0]['table'])->createQueryBuilder();
    }

    /**
     * @param QueryBuilder $qb
     * @param array $value
     */
    protected function addJoin(QueryBuilder $qb, $value)
    {
        /** @var Expr\From[] $from */
        $from = $qb->getDQLPart('from');
        if ($from) {
            $usedAliases = [$from[0]->getAlias()];
        } else {
            $usedAliases = ['t1'];
        }

        $knownAliases = [$usedAliases[0]];
        if (isset($value['join']['inner'])) {
            foreach ($value['join']['inner'] as $join) {
                $knownAliases[] = $join['alias'];
            }
        }
        if (isset($value['join']['left'])) {
            foreach ($value['join']['left'] as $join) {
                $knownAliases[] = $join['alias'];
            }
        }
        $knownAliases = array_unique($knownAliases);
        $qbTools = new QueryBuilderTools();

        // Add joins ordered by used tables
        $tries = 0;
        do {
            $this->addJoinByDefinition($qb, $qbTools, $value, 'inner', $usedAliases, $knownAliases);
            $this->addJoinByDefinition($qb, $qbTools, $value, 'left', $usedAliases, $knownAliases);

            if ($tries > self::MAX_ITERATIONS) {
                throw new \RuntimeException(
                    'Could not reorder joins correctly. Number of tries has exceeded maximum allowed.'
                );
            }
            $tries++;
        } while (count($usedAliases) != count($knownAliases));
    }

    /**
     * @param QueryBuilder $qb
     * @param QueryBuilderTools $qbTools
     * @param array $value
     * @param string $joinType
     * @param array $usedAliases
     * @param array $knownAliases
     */
    protected function addJoinByDefinition(
        QueryBuilder $qb,
        QueryBuilderTools $qbTools,
        array $value,
        $joinType,
        array &$usedAliases,
        array $knownAliases
    ) {
        $joinType = strtolower($joinType);
        if (!isset($value['join'][$joinType])) {
            return;
        }
        $defaultValues = ['conditionType' => null, 'condition' => null];
        foreach ((array)$value['join'][$joinType] as $join) {
            if (in_array($join['alias'], $usedAliases)) {
                continue;
            }
            $join = array_merge($defaultValues, $join);

            $joinUsedAliases = array_merge(
                $qbTools->getUsedTableAliases($join['join']),
                $qbTools->getTablesUsedInJoinCondition($join['condition'], $knownAliases)
            );
            // Intersect with known aliases to prevent counting aliases from subselects
            $joinUsedAliases = array_intersect($joinUsedAliases, $knownAliases);
            $unknownAliases = array_diff($joinUsedAliases, array_merge($usedAliases, [$join['alias']]));
            if (!empty($unknownAliases)) {
                continue;
            }

            $joinMethod = $joinType . 'Join';
            $qb->$joinMethod($join['join'], $join['alias'], $join['conditionType'], $join['condition']);
            $usedAliases[] = $join['alias'];
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param array $value
     */
    protected function addWhere(QueryBuilder $qb, $value)
    {
        if (isset($value['where'])) {
            if (isset($value['where']['and'])) {
                foreach ((array)$value['where']['and'] as $where) {
                    $qb->andWhere($where);
                }
            }

            if (isset($value['where']['or'])) {
                foreach ((array)$value['where']['or'] as $where) {
                    $qb->orWhere($where);
                }
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param array $value
     */
    protected function addOrder(QueryBuilder $qb, $value)
    {
        if (isset($value['orderBy'])) {
            $qb->resetDQLPart('orderBy');

            foreach ((array)$value['orderBy'] as $order) {
                $qb->addOrderBy($order['column'], $order['dir']);
            }
        }
    }
}
