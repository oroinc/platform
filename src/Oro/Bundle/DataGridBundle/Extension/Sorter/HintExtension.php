<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration as OrmQuery;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;

class HintExtension extends AbstractExtension
{
    /**
     * @var QueryHintResolver
     */
    protected $queryHintResolver;

    /**
     * @var string
     */
    protected $hintName;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @param QueryHintResolver $queryHintResolver
     * @param string $hintName
     * @param int $priority
     */
    public function __construct(QueryHintResolver $queryHintResolver, $hintName, $priority)
    {
        $this->queryHintResolver = $queryHintResolver;
        $this->hintName = $hintName;
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return parent::isApplicable($config) && $config->isOrmDatasource();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $query = $config->getOrmQuery();

        $addHint = true;
        $resolvedHintName = $this->queryHintResolver->resolveHintName($this->hintName);
        $hints = $query->getHints();
        foreach ($hints as $hintKey => $hint) {
            if (is_array($hint)) {
                $hintName = $this->getHintAttribute($hint, OrmQuery::NAME_KEY);
                if ($this->hintName === $hintName || $resolvedHintName === $hintName) {
                    $addHint = false;
                    $hintValue = $this->getHintAttribute($hint, OrmQuery::VALUE_KEY);
                    if (false === $hintValue) {
                        // remove the hint if it was disabled
                        unset($hints[$hintKey]);
                        $query->setHints($hints);
                    }
                    break;
                }
            } elseif ($this->hintName === $hint || $resolvedHintName === $hint) {
                $addHint = false;
                break;
            }
        }
        if ($addHint) {
            $query->addHint($this->hintName);
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
