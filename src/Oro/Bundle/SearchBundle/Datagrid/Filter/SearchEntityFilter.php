<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by an entity for a datasource based on a search index.
 * The entity class is specified in the options -> class parameter.
 */
class SearchEntityFilter extends EntityFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        if (isset($params['class'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['class'] = $params['class'];

            unset($params['class']);
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
        }

        return $this->applyRestrictions($ds, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        throw new \BadMethodCallException('Not implemented');
    }
    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return SearchEntityFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass(): ?string
    {
        $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY);
        if (!$options) {
            return null;
        }

        return $options['class'] ?? null;
    }

    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data): bool
    {
        $entityIds = [];
        foreach ($data['value'] as $entity) {
            $entityId = $this->getEntityIdentifier($entity);
            if (null !== $entityId) {
                $entityIds[] = $entityId;
            }
        }

        $ds->addRestriction(
            Criteria::expr()->in($this->get(FilterUtility::DATA_NAME_KEY), $entityIds),
            FilterUtility::CONDITION_AND
        );

        return true;
    }

    private function getEntityIdentifier(object $entity): mixed
    {
        $result = null;
        $entityClass = ClassUtils::getClass($entity);
        $manager = $this->doctrine->getManagerForClass($entityClass);
        if (null !== $manager) {
            $entityIdentifier = $manager->getClassMetadata($entityClass)->getIdentifierValues($entity);
            if (\count($entityIdentifier) === 1) {
                $result = reset($entityIdentifier);
            }
        }

        return $result;
    }
}
