<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Provider\ChainConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\BuilderAwareInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\GridQueryDesignerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a datagrid query definition created by the query designer is correct and can be executed.
 */
class QueryValidator extends ConstraintValidator
{
    /** @var FilterExecutionContext */
    private $filterExecutionContext;

    /** @var ChainConfigurationProvider */
    private $configurationProvider;

    /** @var Builder */
    private $gridBuilder;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var bool */
    private $isDebug;

    /** @var array [grid name => true, ...] */
    private $processing = [];

    public function __construct(
        FilterExecutionContext $filterExecutionContext,
        ChainConfigurationProvider $configurationProvider,
        Builder $gridBuilder,
        DoctrineHelper $doctrineHelper,
        bool $isDebug
    ) {
        $this->filterExecutionContext = $filterExecutionContext;
        $this->configurationProvider = $configurationProvider;
        $this->gridBuilder = $gridBuilder;
        $this->doctrineHelper = $doctrineHelper;
        $this->isDebug = $isDebug;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Query) {
            throw new UnexpectedTypeException($constraint, Query::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof AbstractQueryDesigner) {
            throw new UnexpectedTypeException($value, AbstractQueryDesigner::class);
        }
        if (!$value instanceof GridQueryDesignerInterface) {
            throw new UnexpectedTypeException($value, GridQueryDesignerInterface::class);
        }

        $uniqueId = $this->doctrineHelper->getSingleEntityIdentifier($value, false) ?: uniqid('grid', true);
        $gridName = $value->getGridPrefix() . $uniqueId;
        if (isset($this->processing[$gridName])) {
            return;
        }

        /**
         * Remember the currently processing datagrid to avoid circular validation of the same grid.
         * Remember the current validation context because it can be changed.
         * It can happen during building the datagrid's accepted datasource
         * if a datagrid has a filter related to Segment entity.
         * In this case this validator is executed by submitting the filter form.
         * An example of such case is pages for building a segment or a marketing list.
         * @see \Oro\Bundle\DataGridBundle\Datagrid\Datagrid::getAcceptedDatasource
         * @see \Oro\Bundle\QueryDesignerBundle\Grid\Extension\OrmDatasourceExtension::visitDatasource
         * @see \Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder::doBuildRestrictions
         */
        $this->processing[$gridName] = true;
        $context = $this->context;

        $builder = $this->getBuilder($gridName);
        $builder->setGridName($gridName);
        $builder->setSource($value);
        $this->filterExecutionContext->enableValidation();
        try {
            $dataGrid = $this->gridBuilder->build($builder->getConfiguration(), new ParameterBag());
            $dataSource = $dataGrid->getAcceptedDatasource();
            if ($dataSource instanceof OrmDatasource) {
                $dataSource->getQueryBuilder()->setMaxResults(1);
            }
            $dataSource->getResults();
        } catch (\Throwable $e) {
            $context->addViolation($this->isDebug ? $e->getMessage() : $constraint->message);
        } finally {
            unset($this->processing[$gridName]);
            $this->filterExecutionContext->disableValidation();
        }
    }

    private function getBuilder(string $gridName): DatagridConfigurationBuilder
    {
        /** @var ConfigurationProviderInterface[] $providers */
        $providers = $this->configurationProvider->getProviders();
        foreach ($providers as $provider) {
            if (!$provider instanceof BuilderAwareInterface) {
                continue;
            }
            if ($provider->isApplicable($gridName)) {
                return $provider->getBuilder();
            }
        }

        throw new InvalidConfigurationException(sprintf(
            'A builder for the "%s" data grid is not found.',
            $gridName
        ));
    }
}
