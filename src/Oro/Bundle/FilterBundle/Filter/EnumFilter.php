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
    public const FILTER_TYPE_NAME = 'enum';

    private const CLASS_KEY = 'class';
    private const ENUM_CODE_KEY = 'enum_code';

    protected DictionaryApiEntityManager $dictionaryApiEntityManager;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DictionaryApiEntityManager $dictionaryApiEntityManager
    ) {
        parent::__construct($factory, $util);
        $this->dictionaryApiEntityManager = $dictionaryApiEntityManager;
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
                self::CLASS_KEY => ExtendHelper::buildEnumValueClassName($params[self::ENUM_CODE_KEY])
            ];
            $params[self::CLASS_KEY] = ExtendHelper::buildEnumValueClassName($params[self::ENUM_CODE_KEY]);
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
        if ($metadata[self::CLASS_KEY]) {
            $this->dictionaryApiEntityManager->setClass($this->resolveMetadataClass($metadata));
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

    private function resolveMetadataClass(array $metadata): string
    {
        if (\array_key_exists(self::CLASS_KEY, $metadata)
            && str_starts_with($metadata[self::CLASS_KEY], ExtendHelper::ENTITY_NAMESPACE)
        ) {
            return $metadata[self::CLASS_KEY];
        }

        return $this->dictionaryApiEntityManager->resolveEntityClass($metadata[self::CLASS_KEY], true);
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
