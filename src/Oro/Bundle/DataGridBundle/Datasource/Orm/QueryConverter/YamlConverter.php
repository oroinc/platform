<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;

class YamlConverter implements QueryConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function parse($value, QueryBuilder $qb)
    {
        if (!is_array($value)) {
            $value = Yaml::parse($value);
        }

        $processor = new Processor();

        $value = $processor->processConfiguration(new QueryConfiguration(), $value);

        if (!isset($value['from'])) {
            throw new InvalidConfigurationException('Missing mandatory "from" section');
        }

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

        if (isset($value['groupBy'])) {
            $qb->groupBy($value['groupBy']);
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
     * {@inheritdoc}
     */
    public function dump(QueryBuilder $input)
    {
        return '';
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

        $knownAliases = 1;
        if (isset($value['join']['inner'])) {
            $knownAliases += count($value['join']['inner']);
        }
        if (isset($value['join']['left'])) {
            $knownAliases += count($value['join']['left']);
        }
        $qbTools = new QueryBuilderTools();
        $joinTablePaths = $this->getJoinTablePaths($value['join']['inner']);
        $joinTablePaths = array_merge($joinTablePaths, $this->getJoinTablePaths($value['join']['left']));
        $qbTools->setJoinTablePaths($joinTablePaths);

        do {
            $this->addJoinByDefinition($qb, $qbTools, $value, 'inner', $usedAliases);
            $this->addJoinByDefinition($qb, $qbTools, $value, 'left', $usedAliases);
        } while (count($usedAliases) != $knownAliases);
    }

    /**
     * @param array $value
     * @return array
     */
    protected function getJoinTablePaths(array $value)
    {
        $joinTablePaths = [];
        foreach ($value as $join) {
            if (!empty($join['join'])) {
                $joinTablePaths[$join['alias']] = $join['join'];
            }
        }

        return $joinTablePaths;
    }

    /**
     * @param QueryBuilder $qb
     * @param QueryBuilderTools $qbTools
     * @param array $value
     * @param string $joinType
     * @param array $usedAliases
     */
    protected function addJoinByDefinition(
        QueryBuilder $qb,
        QueryBuilderTools $qbTools,
        array $value,
        $joinType,
        array &$usedAliases
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
                $qbTools->getUsedTableAliases($join['condition'])
            );
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
