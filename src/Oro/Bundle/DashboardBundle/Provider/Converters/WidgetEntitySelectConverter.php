<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * The dashboard widget configuration converter for select an entity.
 */
class WidgetEntitySelectConverter extends ConfigValueConverterAbstract
{
    public function __construct(
        protected AclHelper $aclHelper,
        protected EntityNameResolver $entityNameResolver,
        protected DoctrineHelper $doctrineHelper,
        protected string $entityClass
    ) {
    }

    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        $names = [];
        $entities = $this->getEntities($value);
        foreach ($entities as $entity) {
            $names[] = $this->entityNameResolver->getName($entity);
        }

        return empty($names) ? null : implode('; ', $names);
    }

    protected function getEntities(mixed $value): mixed
    {
        if (empty($value)) {
            return [];
        }

        if (!\is_array($value)) {
            $value = [$value];
        }

        $value = array_filter($value);

        $identityField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($this->entityClass);

        $qb = $this->doctrineHelper->createQueryBuilder($this->entityClass, 'e')
            ->where(\sprintf('e.%s IN (:ids)', $identityField))
            ->setParameter('ids', $value);

        return $this->aclHelper->apply($qb)->getResult();
    }

    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        if (null === $value) {
            return $this->getDefaultChoices($config);
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    #[\Override]
    public function getFormValue(array $config, mixed $value): mixed
    {
        if (null === $value) {
            return $this->getDefaultChoices($config);
        }

        return parent::getFormValue($config, $value);
    }

    protected function getDefaultChoices(array $config): mixed
    {
        return $config['converter_attributes']['default_selected'] ?? [];
    }
}
