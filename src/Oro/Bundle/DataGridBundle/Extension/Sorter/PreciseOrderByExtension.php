<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration as OrmQuery;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

/**
 * @see Oro\Component\DoctrineUtils\ORM\Walker\PreciseOrderByWalker
 */
class PreciseOrderByExtension extends AbstractExtension
{
    const HINT_PRECISE_ORDER_BY = 'HINT_PRECISE_ORDER_BY';

    /** @var QueryHintResolver */
    protected $queryHintResolver;

    /**
     * @param QueryHintResolver $queryHintResolver
     */
    public function __construct(QueryHintResolver $queryHintResolver)
    {
        $this->queryHintResolver = $queryHintResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should visit after all extensions and after SorterExtension
        return -261;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->isOrmDatasource();
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $query = $config->getOrmQuery();

        $addHint = true;
        $resolvedHintName = $this->queryHintResolver->resolveHintName(self::HINT_PRECISE_ORDER_BY);
        $hints = $query->getHints();
        foreach ($hints as $hintKey => $hint) {
            if (is_array($hint)) {
                $hintName = $this->getHintAttribute($hint, OrmQuery::NAME_KEY);
                if (self::HINT_PRECISE_ORDER_BY === $hintName || $resolvedHintName === $hintName) {
                    $addHint = false;
                    $hintValue = $this->getHintAttribute($hint, OrmQuery::VALUE_KEY);
                    if (false === $hintValue) {
                        // remove the hint if it was disabled
                        unset($hints[$hintKey]);
                        $query->setHints($hints);
                    }
                    break;
                }
            } elseif (self::HINT_PRECISE_ORDER_BY === $hint || $resolvedHintName === $hint) {
                $addHint = false;
                break;
            }
        }
        if ($addHint) {
            $query->addHint(self::HINT_PRECISE_ORDER_BY);
        }
    }

    /**
     * @param array  $hint
     * @param string $attributeName
     *
     * @return mixed
     */
    private function getHintAttribute(array $hint, $attributeName)
    {
        return array_key_exists($attributeName, $hint)
            ? $hint[$attributeName]
            : null;
    }
}
