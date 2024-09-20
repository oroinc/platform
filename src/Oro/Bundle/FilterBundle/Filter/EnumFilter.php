<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\EntityBundle\Provider\DictionaryEntityDataProvider;
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
    public const FILTER_TYPE_NAME = 'enum';

    private const CLASS_KEY = 'class';
    private const ENUM_CODE_KEY = 'enum_code';

    private DictionaryEntityDataProvider $dictionaryEntityDataProvider;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DictionaryEntityDataProvider $dictionaryEntityDataProvider
    ) {
        parent::__construct($factory, $util);
        $this->dictionaryEntityDataProvider = $dictionaryEntityDataProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'dictionary';
        if (isset($params[self::CLASS_KEY])) {
            $params[FilterUtility::FORM_OPTIONS_KEY][self::CLASS_KEY] = $params[self::CLASS_KEY];
            unset($params[self::CLASS_KEY]);
        }
        if (isset($params[self::ENUM_CODE_KEY])) {
            $params[FilterUtility::FORM_OPTIONS_KEY] = [
                self::ENUM_CODE_KEY => $params[self::ENUM_CODE_KEY],
                self::CLASS_KEY => ExtendHelper::getOutdatedEnumOptionClassName($params[self::ENUM_CODE_KEY])
            ];
            $params[self::CLASS_KEY] = ExtendHelper::getOutdatedEnumOptionClassName($params[self::ENUM_CODE_KEY]);
            unset($params[self::ENUM_CODE_KEY]);
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        if (isset($metadata[self::CLASS_KEY])) {
            $ids = $this->getForm()->get('value')->getData();
            $metadata['initialData'] = $ids
                ? $this->dictionaryEntityDataProvider->getValuesByIds($metadata[self::CLASS_KEY], $ids)
                : [];
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
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterName = $ds->generateParameterName($this->getName());
        if ($this->isValueRequired($comparisonType)) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return $this->buildComparisonExpr($ds, $comparisonType, $fieldName, $parameterName);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return EnumFilterType::class;
    }

    /**
     * {@inheritDoc}
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
