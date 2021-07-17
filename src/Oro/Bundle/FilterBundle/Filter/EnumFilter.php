<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by an enum entity.
 */
class EnumFilter extends BaseMultiChoiceFilter
{
    const FILTER_TYPE_NAME = 'enum';

    /** @var DictionaryApiEntityManager */
    protected $dictionaryApiEntityManager;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DictionaryApiEntityManager $dictionaryApiEntityManager
    ) {
        parent::__construct($factory, $util);
        $this->dictionaryApiEntityManager = $dictionaryApiEntityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'dictionary';
        if (isset($params['class'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['class'] = $params['class'];
            unset($params['class']);
        }
        if (isset($params['enum_code'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY] = [
                'enum_code' => $params['enum_code'],
                'class' => ExtendHelper::buildEnumValueClassName($params['enum_code'])
            ];
            $params['class'] = ExtendHelper::buildEnumValueClassName($params['enum_code']);
            unset($params['enum_code']);
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        if ($metadata['class']) {
            $resolvedEntityClass = $this->resolveMetadataClass($metadata);
            $this->dictionaryApiEntityManager->setClass($resolvedEntityClass);
            $metadata['initialData'] = $this->dictionaryApiEntityManager->findValueByPrimaryKey(
                $this->getForm()->get('value')->getData()
            );
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * @param array $metadata
     *
     * @return string
     */
    private function resolveMetadataClass(array $metadata)
    {
        if (array_key_exists('class', $metadata) && strpos($metadata['class'], ExtendHelper::ENTITY_NAMESPACE) === 0) {
            return $metadata['class'];
        }

        return $this->dictionaryApiEntityManager->resolveEntityClass($metadata['class'], true);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterName = $ds->generateParameterName($this->getName());
        if ($this->isValueRequired($comparisonType)) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return $this->buildComparisonExpr(
            $ds,
            $comparisonType,
            $fieldName,
            $parameterName
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return EnumFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        switch ($comparisonType) {
            case DictionaryFilterType::TYPE_NOT_IN:
                return $ds->expr()->orX(
                    $ds->expr()->isNull($fieldName),
                    $ds->expr()->notIn($fieldName, $parameterName, true)
                );
            case DictionaryFilterType::EQUAL:
                return $ds->expr()->eq($fieldName, $parameterName, true);
            case DictionaryFilterType::NOT_EQUAL:
                return $ds->expr()->orX(
                    $ds->expr()->isNull($fieldName),
                    $ds->expr()->neq($fieldName, $parameterName, true)
                );
            default:
                return $ds->expr()->in($fieldName, $parameterName, true);
        }
    }
}
