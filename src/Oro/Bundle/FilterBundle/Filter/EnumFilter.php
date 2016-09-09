<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class EnumFilter extends BaseMultiChoiceFilter
{
    /** @var DictionaryApiEntityManager */
    protected $dictionaryApiEntityManager;

    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     */
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
            $this->dictionaryApiEntityManager->setClass(
                $this->dictionaryApiEntityManager->resolveEntityClass($metadata['class'], true)
            );
            $metadata['initialData'] = $this->dictionaryApiEntityManager->findValueByPrimaryKey(
                $this->getForm()->get('value')->getData()
            );
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterName = $ds->generateParameterName($this->getName());
        if (!in_array($comparisonType, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
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
        return EnumFilterType::NAME;
    }
}
