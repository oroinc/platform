<?php

namespace Oro\Bundle\WorkflowBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowTemplate;

class WorkflowFilter extends EntityFilter
{
    /** @var TranslationKeyGenerator */
    protected $generator;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param TranslationKeyGenerator $generator
     */
    public function __construct(FormFactoryInterface $factory, FilterUtility $util, TranslationKeyGenerator $generator)
    {
        parent::__construct($factory, $util);

        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['class'] = WorkflowDefinition::class;
        $params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['multiple'] = false;

        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        /* @var $definition WorkflowDefinition */
        $definition = reset($data['value']);

        $keyParameter = $ds->generateParameterName('key');
        $domainParameter = $ds->generateParameterName('domain');

        $expr = $ds->expr()->andX(
            $ds->expr()->eq(sprintf('%s.domain', $fieldName), $domainParameter, true),
            $ds->expr()->like(sprintf('%s.key', $fieldName), $keyParameter, true)
        );

        $key = $this->generator->generate(
            new TranslationKeySource(new WorkflowTemplate(), ['workflow_name' => $definition->getName()])
        );

        $ds->setParameter($keyParameter, $key . '%');
        $ds->setParameter($domainParameter, 'workflows');

        return $expr;
    }

    /**
     * {@inheritdoc}
     */
    protected function findRelatedJoin(FilterDatasourceAdapterInterface $ds)
    {
        // nothing todo, related joins configured manually
    }
}
