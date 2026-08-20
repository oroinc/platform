<?php

namespace Oro\Bundle\DataAuditBundle\Filter;

use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditFieldLabelProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityAuditFieldSearchProvider;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Grid filter that searches within the changed audit data.
 *
 * The audit grid's "Data" column is built after the query has run (see Datagrid/Property/data.html.twig),
 * so it cannot be filtered directly; this filter applies a correlated EXISTS subquery over the related
 * AuditField rows instead. It matches
 *  - the changed field key (a configuration key or an entity field name),
 *  - the old and the new text value,
 *  - every configuration setting whose displayed breadcrumb contains the term, so any part of
 *    "Commerce › Product › Promotions › Maximum Items" finds the record,
 *  - and every audited entity field whose displayed label contains the term ("Primary Email" finds the
 *    change of User::email).
 *
 * The names are resolved from the current translations, so searching works in the viewer's own language.
 * Values stored in a typed column (boolean, integer, float, array) are matched by name, not by value.
 */
class AuditDataFilter extends StringFilter
{
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $filterUtility,
        private readonly ConfigAuditFieldLabelProvider $configFieldLabelProvider,
        private readonly EntityAuditFieldSearchProvider $entityFieldSearchProvider
    ) {
        parent::__construct($factory, $filterUtility);
    }

    #[\Override]
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        // The backend filter type is "audit-data" (custom EXISTS apply over audit fields), but the
        // frontend has no "audit-data" widget. Render it as the built-in text ("string") filter so
        // the frontend maps it to an existing JS module; the backend lookup still uses "audit-data".
        $metadata['type'] = 'string';

        return $metadata;
    }

    #[\Override]
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, OrmFilterDatasourceAdapter::class);
        }

        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        [$rootAlias] = $ds->getQueryBuilder()->getRootAliases();
        $fieldAlias = $ds->generateParameterName('adf');

        // The columns that hold the changed data as searchable text.
        $term = $this->addParameter($ds, 'audit_data', $data['value']);
        $conditions = array_map(
            static fn (string $column): string => sprintf('LOWER(%s.%s) LIKE LOWER(%s)', $fieldAlias, $column, $term),
            ['field', 'oldText', 'newText']
        );

        // StringFilter::parseValue() wrapped the term in "%...%"; the names are matched against the raw
        // term. Configuration keys are globally unique, so they need no object class scoping.
        $rawTerm = trim((string)$data['value'], '%');
        $configKeys = $this->configFieldLabelProvider->getMatchingFieldKeys($rawTerm);
        if ($configKeys) {
            $conditions[] = sprintf(
                '%s.field IN (%s)',
                $fieldAlias,
                $this->addParameter($ds, 'audit_config_keys', $configKeys)
            );
        }

        // Entity field names are not unique across entities, so they are scoped by the matching classes.
        $entityFields = $this->entityFieldSearchProvider->getMatchingFields($rawTerm);
        if ($entityFields['fields']) {
            $conditions[] = sprintf(
                '(%s.objectClass IN (%s) AND %s.field IN (%s))',
                $rootAlias,
                $this->addParameter($ds, 'audit_classes', $entityFields['classes']),
                $fieldAlias,
                $this->addParameter($ds, 'audit_fields', $entityFields['fields'])
            );
        }

        $expr = $ds->expr()->exists(sprintf(
            'SELECT 1 FROM %s %s WHERE %s.audit = %s AND (%s)',
            AuditField::class,
            $fieldAlias,
            $fieldAlias,
            $rootAlias,
            implode(' OR ', $conditions)
        ));
        if (TextFilterType::TYPE_NOT_CONTAINS === $data['type']) {
            $expr = $ds->expr()->not($expr);
        }

        $this->applyFilterToClause($ds, $expr);

        return true;
    }

    /**
     * Binds a value and returns its DQL placeholder.
     */
    private function addParameter(OrmFilterDatasourceAdapter $ds, string $name, mixed $value): string
    {
        $parameterName = $ds->generateParameterName($name);
        $ds->setParameter($parameterName, $value);

        return ':' . $parameterName;
    }
}
