<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a multi-enum entity.
 */
class MultiEnumFilter extends BaseMultiChoiceFilter
{
    public const string FILTER_TYPE_NAME = 'multi_enum';
    private const string CLASS_KEY = 'class';
    private const string ENUM_CODE_KEY = 'enum_code';

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
    ) {
        parent::__construct($factory, $util);
    }

    #[\Override]
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        return $this->buildComparisonExpr(
            $ds,
            $comparisonType,
            $fieldName,
            $data['value']
        );
    }

    #[\Override]
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'dictionary';
        if (isset($params['class'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['class'] = $params['class'];
            unset($params['class']);
        }
        if (isset($params[self::ENUM_CODE_KEY])) {
            $params[FilterUtility::FORM_OPTIONS_KEY][self::ENUM_CODE_KEY] = $params[self::ENUM_CODE_KEY];
            $params[FilterUtility::FORM_OPTIONS_KEY][self::CLASS_KEY] =
                ExtendHelper::getOutdatedEnumOptionClassName($params[self::ENUM_CODE_KEY]);
            $params[self::CLASS_KEY] = ExtendHelper::getOutdatedEnumOptionClassName($params[self::ENUM_CODE_KEY]);

            unset($params[self::ENUM_CODE_KEY]);
        }

        parent::init($name, $params);
    }

    #[\Override]
    public function prepareData(array $data): array
    {
        return $data;
    }

    #[\Override]
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        mixed $comparisonType,
        mixed $fieldName,
        mixed $parameterName
    ) {
        list($fieldPathValue, $fieldNameValue) = $this->parseFunctionArguments($fieldName);
        if (null !== $fieldNameValue) {
            $fieldPath = $fieldPathValue;
            $fieldName = $fieldNameValue;
        } else {
            $fieldValue = $ds->getFieldByAlias($fieldName);
            $fieldPath = 'serialized_data';
            list($fieldPathValue, $fieldNameValue) = $this->parseFunctionArguments($fieldValue);
            if (null !== $fieldNameValue) {
                $fieldPath = $fieldPathValue;
                $fieldName = $fieldNameValue;
            }
        }
        $existOptionPart = QueryBuilderUtil::sprintf("JSON_EXTRACT(%s, '%s')", $fieldPath, $fieldName);
        switch ($comparisonType) {
            case DictionaryFilterType::TYPE_NOT_IN:
                return $ds->expr()->orX(
                    $ds->expr()->isNull($existOptionPart),
                    $ds->expr()->andX(
                        ...$this->getEnumOptionsExpr($ds, $parameterName, $fieldPath, $fieldName, 'false')
                    )
                );
            case DictionaryFilterType::NOT_EQUAL:
                return $ds->expr()->orX(
                    $ds->expr()->isNull($existOptionPart),
                    $ds->expr()->eq($this->getContainsExpr($fieldPath, $fieldName, $parameterName), 'false')
                );
            case DictionaryFilterType::TYPE_IN:
                return $ds->expr()->orX(
                    ...$this->getEnumOptionsExpr($ds, $parameterName, $fieldPath, $fieldName, 'true')
                );
            default:
                return $ds->expr()->eq($this->getContainsExpr($fieldPath, $fieldName, $parameterName), 'true');
        }
    }

    private function parseFunctionArguments(string $fieldDataValue): array
    {
        if (preg_match('/JSON_EXTRACT\(([^,]+),\s*\'([^\']+)\'\)/', $fieldDataValue, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [null, null];
    }

    private function getContainsExpr(string $fieldPath, string $fieldName, mixed $parameterName): string
    {
        return QueryBuilderUtil::sprintf(
            "JSONB_ARRAY_CONTAINS_JSON(%s, '%s', '\"%s\"')",
            $fieldPath,
            $fieldName,
            $parameterName
        );
    }

    private function getEnumOptionsExpr(
        FilterDatasourceAdapterInterface $ds,
        $parameterValue,
        $fieldPath,
        $fieldName,
        string $compareValue
    ): array {
        QueryBuilderUtil::checkParameter($compareValue);

        return array_map(
            fn ($optionId) => $ds->expr()->eq(
                $this->getContainsExpr($fieldPath, $fieldName, $optionId),
                $compareValue
            ),
            $parameterValue
        );
    }

    #[\Override]
    protected function getFormType(): string
    {
        return EnumFilterType::class;
    }
}
