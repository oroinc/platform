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
    private WorkflowTranslationHelper $translationHelper;

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
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        foreach ($this->getFieldOptions() as $key => $value) {
            $params[FilterUtility::FORM_OPTIONS_KEY][self::FIELD_OPTIONS_KEY][$key] = $value;
        }

        parent::init($name, $params);
    }

    protected function getFieldOptions(): array
    {
        return [
            'class'                => WorkflowDefinition::class,
            'multiple'             => true,
            'choice_label'         => function (WorkflowDefinition $definition) {
                return $this->translationHelper->findTranslation($definition->getLabel());
            }
        ];
    }
}
