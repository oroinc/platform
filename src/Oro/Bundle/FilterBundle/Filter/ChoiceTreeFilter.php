<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Event\ChoiceTreeFilterLoadDataEvent;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceTreeFilterType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * The filter by extendable list of values.
 */
class ChoiceTreeFilter extends AbstractFilter implements FilterPrepareDataInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var RouterInterface */
    protected $router;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * ChoiceTreeFilter constructor.
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param ManagerRegistry $registry
     * @param RouterInterface $router
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $registry,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($factory, $util);
        $this->registry = $registry;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ChoiceTreeFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $entities = [];
        if ($this->getOr('className') && $this->state) {
            $data = $this->parseData($this->state);

            $event = new ChoiceTreeFilterLoadDataEvent($this->getOr('className'), $data['value']);
            $this->eventDispatcher->dispatch(ChoiceTreeFilterLoadDataEvent::EVENT_NAME, $event);
            $entities = $event->getData();
        }

        $metadata[FilterUtility::TYPE_KEY] = 'choice-tree';
        $metadata['data'] = $entities;
        $metadata['autocomplete_alias'] = $this->getAutocompleteAlias();
        $metadata['autocomplete_url'] = $this->getAutocompleteUrl();
        $metadata['renderedPropertyName'] = $this->getRenderedPropertyName();

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (count($data['value']) > 1 || (isset($data['value'][0]) && '' != $data['value'][0])) {
            $parameterName = $ds->generateParameterName($this->getName());

            if ($this->isValueRequired($comparisonType)) {
                $ds->setParameter($parameterName, $data['value']);
            }

            return $fieldName . ' in (:'. $parameterName .')';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);
        $data['value'] = explode(',', $data['value']);

        return $data;
    }

    /**
     * @return bool|mixed
     */
    protected function getAutocompleteAlias()
    {
        return $this->getOr('autocomplete_alias') ?: false;
    }

    /**
     * @return string
     */
    protected function getAutocompleteUrl()
    {
        return $this->router->generate($this->getOr('autocomplete_url') ?: 'oro_form_autocomplete_search');
    }

    /**
     * @return bool|mixed
     */
    protected function getRenderedPropertyName()
    {
        return $this->getOr('renderedPropertyName') ?: false;
    }
}
