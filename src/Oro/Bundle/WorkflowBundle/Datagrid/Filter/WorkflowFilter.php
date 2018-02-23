<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\Form\FormFactoryInterface;

class WorkflowFilter extends EntityFilter
{
    /** @var WorkflowTranslationHelper */
    protected $translationHelper;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param WorkflowTranslationHelper $translationHelper
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        WorkflowTranslationHelper $translationHelper
    ) {
        parent::__construct($factory, $util);

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
            'class' => WorkflowDefinition::class,
            'multiple' => true,
            'choice_label' => [$this, 'getLabel'],
            'translatable_options' => false
        ];
    }

    /**
     * @param WorkflowDefinition $definition
     * @return string
     */
    public function getLabel(WorkflowDefinition $definition)
    {
        return $this->translationHelper->findTranslation($definition->getLabel());
    }
}
