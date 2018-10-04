<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Provider\ChainConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\BuilderAwareInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\GridQueryDesignerInterface;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\QueryConstraint;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a query built via the query designer is correct and can be executed.
 */
class QueryValidator extends ConstraintValidator
{
    /** @var ChainConfigurationProvider */
    private $configurationProvider;

    /** @var Builder */
    private $gridBuilder;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var TranslatorInterface */
    private $translator;

    /** @var bool */
    private $isDebug;

    /** @var array [grid name => true, ...] */
    private $processing = [];

    /**
     * @param ChainConfigurationProvider $configurationProvider
     * @param Builder                    $gridBuilder
     * @param DoctrineHelper             $doctrineHelper
     * @param TranslatorInterface        $translator
     * @param bool                       $isDebug
     */
    public function __construct(
        ChainConfigurationProvider $configurationProvider,
        Builder $gridBuilder,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator,
        $isDebug
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->gridBuilder = $gridBuilder;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
        $this->isDebug = $isDebug;
    }

    /**
     * @param GridQueryDesignerInterface|AbstractQueryDesigner $value
     * @param QueryConstraint|Constraint                       $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof GridQueryDesignerInterface) {
            return;
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
         * @see \Oro\Bundle\SegmentBundle\Model\DatagridSourceSegmentProxy::getDefinition
         */
        $this->processing[$gridName] = true;
        $context = $this->context;

        $builder = $this->getBuilder($gridName);
        $builder->setGridName($gridName);
        $builder->setSource($value);
        try {
            $dataGrid = $this->gridBuilder->build($builder->getConfiguration(), new ParameterBag());
            $dataSource = $dataGrid->getAcceptedDatasource();
            if ($dataSource instanceof OrmDatasource) {
                $dataSource->getQueryBuilder()->setMaxResults(1);
            }
            $dataSource->getResults();
        } catch (\Throwable $e) {
            $context->addViolation(
                $this->isDebug ? $e->getMessage() : $this->translator->trans($constraint->message)
            );
        } finally {
            unset($this->processing[$gridName]);
        }
    }

    /**
     * @param string $gridName
     *
     * @return DatagridConfigurationBuilder
     */
    private function getBuilder($gridName)
    {
        foreach ($this->configurationProvider->getProviders() as $provider) {
            /** @var ConfigurationProviderInterface|BuilderAwareInterface $provider */
            if (!$provider instanceof BuilderAwareInterface) {
                continue;
            }

            if ($provider->isApplicable($gridName)) {
                return $provider->getBuilder();
            }
        }

        throw new InvalidConfigurationException('Builder is missing');
    }
}
