<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Provider\ChainConfigurationProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\QueryConstraint;
use Oro\Bundle\QueryDesignerBundle\Validator\QueryValidator;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class QueryValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryValidator */
    private $validator;

    /** @var QueryConstraint */
    private $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $gridBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ChainConfigurationProvider::class);
        $this->gridBuilder = $this->createMock(Builder::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->validator = new QueryValidator(
            $this->configurationProvider,
            $this->gridBuilder,
            $this->doctrineHelper,
            $this->translator,
            false
        );
        $this->validator->setFilterExecutionContext(new FilterExecutionContext());

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);

        $this->constraint = new QueryConstraint();

        $this->translator->expects(self::any())
            ->method('trans')
            ->with($this->constraint->message)
            ->willReturn('Invalid query');
    }

    public function testValidateNotMatchedQuery()
    {
        $this->context->expects(self::never())
            ->method('addViolation');

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $datasource
     * @param bool                                     $useOrmDatasource
     * @param \Exception                               $exception
     * @param \Exception                               $configurationException
     * @param int                                      $expectsCount
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate($datasource, $useOrmDatasource, $exception, $configurationException, $expectsCount)
    {
        $value = new QueryDesignerModel();
        $this->doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(123);

        $provider = $this->createMock(ReportDatagridConfigurationProvider::class);
        $builder = $this->createMock(DatagridConfigurationBuilder::class);

        $provider->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);
        $provider->expects(self::once())
            ->method('getBuilder')
            ->willReturn($builder);
        $this->configurationProvider->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider, new \stdClass()]);

        if ($configurationException) {
            $builder->expects(self::once())
                ->method('getConfiguration')
                ->will(self::throwException($configurationException));
        } else {
            $configuration = $this->createMock(DatagridConfiguration::class);
            $builder->expects(self::once())
                ->method('getConfiguration')
                ->willReturn($configuration);
        }
        $datagrid = $this->createMock(DatagridInterface::class);

        $this->gridBuilder->expects(self::exactly($expectsCount))
            ->method('build')
            ->willReturn($datagrid);
        $datagrid->expects(self::exactly($expectsCount))
            ->method('getAcceptedDatasource')
            ->willReturnCallback(function () use ($datasource, $value) {
                $this->validator->initialize($this->createMock(ExecutionContextInterface::class));
                $this->validator->validate($value, $this->constraint);

                return $datasource;
            });
        $qb = $this->createMock(QueryBuilder::class);
        $datasource->expects(self::exactly($expectsCount))
            ->method('getQueryBuilder')
            ->willReturn($qb);

        if ($useOrmDatasource) {
            $qb->expects(self::exactly($expectsCount))
                ->method('setMaxResults')
                ->will(self::returnSelf());
        }
        if ($exception) {
            $datasource->expects(self::exactly($expectsCount))
                ->method('getResults')
                ->will(self::throwException($exception));
        } else {
            $datasource->expects(self::exactly($expectsCount))
                ->method('getResults')
                ->willReturn([]);
        }

        if ($exception || $configurationException) {
            $this->context->expects(self::once())
                ->method('addViolation');
        } else {
            $this->context->expects(self::never())
                ->method('addViolation');
        }

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                $this->createMock(OrmDatasource::class),
                false,
                new DBALException('failed'),
                null,
                1
            ],
            [
                $this->createMock(OrmDatasource::class),
                false,
                new InvalidConfigurationException(),
                null,
                1
            ],
            [
                $this->createMock(OrmDatasource::class),
                false,
                null,
                null,
                1
            ],
            [
                $this->createMock(OrmDatasource::class),
                true,
                new DBALException('failed'),
                null,
                1
            ],
            [
                $this->createMock(OrmDatasource::class),
                true,
                new InvalidConfigurationException(),
                null,
                1
            ],
            [
                $this->createMock(OrmDatasource::class),
                false,
                null,
                new InvalidConfigurationException(),
                0
            ]
        ];
    }

    public function testBuilderIsMissing()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A builder for the "test_grid_1" data grid is not found.');

        $value = new QueryDesignerModel();
        $this->doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);

        $this->configurationProvider->expects(self::once())
            ->method('getProviders')
            ->willReturn([]);

        $this->validator->validate($value, $this->constraint);
    }

    public function testExistingEntityValidation()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A builder for the "test_grid_1" data grid is not found.');

        $value = new QueryDesignerModel();
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);

        $provider = $this->createMock(ReportDatagridConfigurationProvider::class);
        $provider->expects(self::once())
            ->method('isApplicable')
            ->with(QueryDesignerModel::GRID_PREFIX . '1')
            ->willReturn(false);
        $this->configurationProvider->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider]);

        $this->validator->validate($value, $this->constraint);
    }

    public function testNewEntityValidation()
    {
        $this->expectException(InvalidConfigurationException::class);

        $value = new QueryDesignerModel();
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);

        $provider = $this->createMock(ReportDatagridConfigurationProvider::class);
        $provider->expects(self::once())
            ->method('isApplicable')
            ->with(self::stringStartsWith(QueryDesignerModel::GRID_PREFIX))
            ->willReturn(false);
        $this->configurationProvider->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider]);

        $this->validator->validate($value, $this->constraint);
    }
}
