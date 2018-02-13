<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class WorkflowTranslationFilter extends WorkflowFilter
{
    /** @var TranslationKeyGenerator */
    protected $generator;

    /**
     * {@inheritdoc}
     */
    protected function getFieldOptions()
    {
        return array_merge(parent::getFieldOptions(), ['multiple' => false]);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        QueryBuilderUtil::checkIdentifier($fieldName);
        /* @var $definition WorkflowDefinition */
        $definition = reset($data['value']);

        $keyParameter = $ds->generateParameterName('key');
        $domainParameter = $ds->generateParameterName('domain');

        $expr = $ds->expr()->andX(
            $ds->expr()->eq(sprintf('%s.domain', $fieldName), $domainParameter, true),
            $ds->expr()->like(sprintf('%s.key', $fieldName), $keyParameter, true)
        );

        $key = $this->getGenerator()->generate(
            new TranslationKeySource(new WorkflowTemplate(), ['workflow_name' => $definition->getName()])
        );

        $ds->setParameter($keyParameter, $key . '%');
        $ds->setParameter($domainParameter, 'workflows');

        return $expr;
    }

    /**
     * @return TranslationKeyGenerator
     */
    protected function getGenerator()
    {
        if (!$this->generator) {
            $this->generator = new TranslationKeyGenerator();
        }

        return $this->generator;
    }

    /**
     * {@inheritdoc}
     */
    protected function findRelatedJoin(FilterDatasourceAdapterInterface $ds)
    {
        // related joins configured manually
    }
}
