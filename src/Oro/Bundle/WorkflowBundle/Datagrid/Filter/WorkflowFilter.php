<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a workflow.
 */
class WorkflowFilter extends EntityFilter
{
    /** @var WorkflowTranslationHelper */
    private $translationHelper;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $doctrine,
        WorkflowTranslationHelper $translationHelper
    ) {
        parent::__construct($factory, $util, $doctrine);
        $this->translationHelper = $translationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        foreach ($this->getFieldOptions() as $key => $value) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['field_options'][$key] = $value;
        }

        parent::init($name, $params);
    }

    /**
     * @return array
     */
    protected function getFieldOptions()
    {
        return [
            'class'                => WorkflowDefinition::class,
            'multiple'             => true,
            'choice_label'         => function (WorkflowDefinition $definition) {
                return $this->translationHelper->findTranslation($definition->getLabel());
            },
            'translatable_options' => false
        ];
    }
}
