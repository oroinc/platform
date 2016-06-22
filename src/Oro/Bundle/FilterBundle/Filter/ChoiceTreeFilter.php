<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceTreeFilterType;

class ChoiceTreeFilter extends AbstractFilter
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var RouterInterface */
    protected $router;
    
    /**
     * ChoiceTreeFilter constructor.
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param ManagerRegistry $registry
     * @param RouterInterface $router
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $registry,
        RouterInterface $router
    ) {
        parent::__construct($factory, $util);
        $this->registry = $registry;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ChoiceTreeFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata[FilterUtility::TYPE_KEY] = 'choice-tree';
        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);
        $metadata['data'] = isset($options['data']) ? $options['data'] : false;
        $metadata['autocomplete_alias'] = $this->getOr('autocomplete_alias') ?
            $this->getOr('autocomplete_alias') : false;
        $routeName = $this->getOr('autocomplete_url') ?
            $this->getOr('autocomplete_url') : 'oro_form_autocomplete_search';
        $metadata['autocomplete_url'] = $this->router->generate($routeName);
        $metadata['renderedPropertyName'] = $this->getOr('renderedPropertyName') ?
            $this->getOr('renderedPropertyName') : false;

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type =  $data['type'];
        if (count($data['value']) > 1 || (isset($data['value'][0]) && $data['value'][0] != "")) {
            $parameterName = $ds->generateParameterName($this->getName());

            $this->applyFilterToClause(
                $ds,
                $this->get(FilterUtility::DATA_NAME_KEY) . ' in (:'. $parameterName .')'
            );

            if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
                $ds->setParameter($parameterName, $data['value']);
            }
        }
        return true;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function parseData($data)
    {
        $data['value'] = explode(',', $data['value']);
        return $data;
    }
}
