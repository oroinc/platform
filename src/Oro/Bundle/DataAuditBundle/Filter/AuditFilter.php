<?php

namespace Oro\Bundle\DataAuditBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Form\Type\FilterType;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * The filter by a modification date of an auditable field.
 */
class AuditFilter extends EntityFilter
{
    const TYPE_CHANGED = 'changed';
    const TYPE_CHANGED_TO_VALUE = 'changed_to_value';

    /** @var FilterExecutionContext */
    protected $filterExecutionContext;

    /** @var QueryDesignerManager */
    protected $queryDesignerManager;

    /** @var string */
    protected $auditAlias;

    /** @var string */
    protected $auditFieldAlias;

    /** @var string */
    protected $fieldParam;

    /** @var string */
    protected $objectClassParam;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $doctrine,
        FilterExecutionContext $filterExecutionContext,
        QueryDesignerManager $queryDesignerManager
    ) {
        parent::__construct($factory, $util, $doctrine);
        $this->filterExecutionContext = $filterExecutionContext;
        $this->queryDesignerManager = $queryDesignerManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, OrmFilterDatasourceAdapter::class);
        }

        $this->auditAlias = $ds->generateParameterName('a');
        $this->auditFieldAlias = $ds->generateParameterName('f');
        $this->fieldParam = $ds->generateParameterName('field');
        $this->objectClassParam = $ds->generateParameterName('objectClass');

        $qb = $ds->getQueryBuilder();

        $fieldName = $this->getField($data['auditFilter']['columnName']);
        [$objectAlias] = $qb->getRootAliases();
        $objectClass = $this->getClass($data['auditFilter']['columnName'], $qb->getRootEntities());
        $metadata = $qb->getEntityManager()->getClassMetadata($objectClass);

        if ($metadata->isIdentifierComposite) {
            throw new \LogicException('Composite identifiers are not supported.');
        }

        $identifier = $metadata->getIdentifier()[0];

        $auditQb = $qb
            ->getEntityManager()
            ->getRepository('OroDataAuditBundle:Audit')
            ->createQueryBuilder($this->auditAlias);

        $auditQb
            ->select('1')
            ->andWhere(sprintf('%s.objectClass = :%s', $this->auditAlias, $this->objectClassParam))
            ->andWhere(sprintf('%s.objectId = %s.%s', $this->auditAlias, $objectAlias, $identifier))
            ->setParameter($this->objectClassParam, $objectClass)
            ->setMaxResults(1);

        $auditQb
            ->join(
                sprintf('%s.fields', $this->auditAlias),
                $this->auditFieldAlias,
                Expr\Join::WITH,
                sprintf('%s.field = :%s', $this->auditFieldAlias, $this->fieldParam)
            )
            ->setParameter($this->fieldParam, $fieldName);

        $auditDs = new OrmFilterDatasourceAdapter($auditQb);
        $this->applyFilter(
            $auditDs,
            'datetime',
            sprintf('%s.loggedAt', $this->auditAlias),
            $data['auditFilter']['data']
        );
        $this->applyNewAuditValueFilter($auditDs, $objectClass, $fieldName, $data);

        $dql = $auditQb->getQuery()->getDQL();

        $this->applyFilterToClause($ds, $ds->expr()->exists($dql));

        foreach ($auditQb->getParameters() as $parameter) {
            $qb->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string $objectClass
     * @param string $fieldName
     * @param array $data
     */
    protected function applyNewAuditValueFilter(OrmFilterDatasourceAdapter $ds, $objectClass, $fieldName, array $data)
    {
        if ($data['auditFilter']['type'] !== static::TYPE_CHANGED_TO_VALUE) {
            return;
        }

        $metadata = $ds->getQueryBuilder()->getEntityManager()->getClassMetadata($objectClass);
        $type = $metadata->getTypeOfField($fieldName);
        if (!$type) {
            $type = 'text';
        }

        $newValueField = sprintf('new%s', ucfirst(AuditFieldTypeRegistry::getAuditType($type)));

        $this->applyFilter(
            $ds,
            $data['filter']['filter'],
            sprintf('%s.%s', $this->auditFieldAlias, $newValueField),
            $data['filter']['data']
        );
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $name
     * @param string $field
     * @param mixed $data
     */
    protected function applyFilter(FilterDatasourceAdapterInterface $ds, $name, $field, $data)
    {
        $filter = $this->queryDesignerManager->createFilter(
            $name,
            [FilterUtility::DATA_NAME_KEY => $field]
        );

        $normalizedData = $this->filterExecutionContext->normalizedFilterData($filter, $data);
        if (null !== $normalizedData) {
            $filter->apply($ds, $normalizedData);
        }
    }

    /**
     * @param string $columnName
     * @param string[] $rootEntities
     *
     * @return string
     */
    protected function getClass($columnName, array $rootEntities)
    {
        if (!str_contains($columnName, '+')) {
            return reset($rootEntities);
        }

        $matches = [];
        preg_match_all('/(?<=\+)[^\+:]+/', $columnName, $matches);

        return end($matches[0]);
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function getField($columnName)
    {
        [, $fieldName] = explode('.', $this->get(FilterUtility::DATA_NAME_KEY));
        if (!str_contains($fieldName, '\\')) {
            return $fieldName;
        }

        $matches = [];
        preg_match('/^[^+]+/', $columnName, $matches);

        return $matches[0];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return FilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function createForm(): FormInterface
    {
        return $this->formFactory->create($this->getFormType());
    }
}
