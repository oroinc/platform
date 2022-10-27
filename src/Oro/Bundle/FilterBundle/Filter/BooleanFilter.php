<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The filter by a boolean value.
 */
class BooleanFilter extends AbstractFilter
{
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(FormFactoryInterface $factory, FilterUtility $util, TranslatorInterface $translator)
    {
        parent::__construct($factory, $util);
        $this->translator = $translator;
    }

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
        $formView = $this->getForm()->createView();
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

        $metadata = parent::getMetadata();
        $metadata['choices'] = $choices;

        if (!empty($metadata['placeholder'])) {
            $metadata['placeholder'] = $this->translator->trans($metadata['placeholder']);
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        if (isset($data['value']) && !\is_int($data['value'])) {
            $data['value'] = (int)$data['value'];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);
        if (!is_array($data)
            || !array_key_exists('value', $data)
            || !$data['value']
            || !in_array($data['value'], [BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO])
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
}
