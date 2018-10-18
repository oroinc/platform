<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Translation\TranslatorInterface;

class BooleanFilter extends AbstractFilter
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return BooleanFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        // static option for metadata
        $params['contextSearch'] = false;
        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        return $this->buildComparisonExpr(
            $ds,
            $data['value'],
            $fieldName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView  = $this->getForm()->createView();
        $fieldView = $formView->children['value'];

        $choices = array_map(
            function (ChoiceView $choice) {
                return [
                    'label' => $choice->label,
                    'value' => $choice->value
                ];
            },
            $fieldView->vars['choices']
        );


        $metadata            = parent::getMetadata();
        $metadata['choices'] = $choices;

        if (!empty($metadata['placeholder'])) {
            $metadata['placeholder'] = $this->translator->trans($metadata['placeholder']);
        }

        return $metadata;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        $allowedValues = array(BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO);
        if (!is_array($data)
            || !array_key_exists('value', $data)
            || !$data['value']
            || !in_array($data['value'], $allowedValues)
        ) {
            return false;
        }

        return $data;
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType 0 to compare with false, 1 to compare with true
     * @param string                           $fieldName
     * @return string
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName
    ) {
        switch ($comparisonType) {
            case BooleanFilterType::TYPE_YES:
                return $ds->expr()->eq($fieldName, 'true');
            default:
                return $ds->expr()->neq($fieldName, 'true');
        }
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
}
