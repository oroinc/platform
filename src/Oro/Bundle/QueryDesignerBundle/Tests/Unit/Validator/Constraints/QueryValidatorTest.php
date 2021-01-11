<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

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
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs\BuilderAwareConfigurationProviderStub;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs\GridAwareQueryDesignerStub;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\Query;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\QueryValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class QueryValidatorTest extends ConstraintValidatorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $gridBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function createValidator(): QueryValidator
    {
        $this->configurationProvider = $this->createMock(ChainConfigurationProvider::class);
        $this->gridBuilder = $this->createMock(Builder::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        return new QueryValidator(
            new FilterExecutionContext(),
            $this->configurationProvider,
            $this->gridBuilder,
            $this->doctrineHelper,
            false
        );
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new GridAwareQueryDesignerStub(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new Query());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new Query());
    }

    public function testUnsupportedQueryDesignerValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new QueryDesigner(), new Query());
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        \PHPUnit\Framework\MockObject\MockObject $datasource,
        bool $useOrmDatasource,
        ?\Exception $exception,
        ?\Exception $configurationException,
        int $expectsCount
    ): void {
        $value = new GridAwareQueryDesignerStub();
        $this->doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(123);

        $provider = $this->createMock(BuilderAwareConfigurationProviderStub::class);
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
                ->willThrowException($configurationException);
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
                $this->validator->validate($value, new Query());

                return $datasource;
            });
        $qb = $this->createMock(QueryBuilder::class);
        $datasource->expects(self::exactly($expectsCount))
            ->method('getQueryBuilder')
            ->willReturn($qb);

        if ($useOrmDatasource) {
            $qb->expects(self::exactly($expectsCount))
                ->method('setMaxResults')
                ->willReturnSelf();
        }
        if ($exception) {
            $datasource->expects(self::exactly($expectsCount))
                ->method('getResults')
                ->willThrowException($exception);
        } else {
            $datasource->expects(self::exactly($expectsCount))
                ->method('getResults')
                ->willReturn([]);
        }

        $constraint = new Query();
        $this->validator->validate($value, new Query());
        if ($exception || $configurationException) {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validateDataProvider(): array
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

    public function testBuilderIsMissing(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A builder for the "test_grid_1" data grid is not found.');

        $value = new GridAwareQueryDesignerStub();
        $this->doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);

        $this->configurationProvider->expects(self::once())
            ->method('getProviders')
            ->willReturn([]);

        $this->validator->validate($value, new Query());
    }

    public function testExistingEntityValidation(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A builder for the "test_grid_1" data grid is not found.');

        $value = new GridAwareQueryDesignerStub();
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);

        $provider = $this->createMock(BuilderAwareConfigurationProviderStub::class);
        $provider->expects(self::once())
            ->method('isApplicable')
            ->with(GridAwareQueryDesignerStub::GRID_PREFIX . '1')
            ->willReturn(false);
        $this->configurationProvider->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider]);

        $this->validator->validate($value, new Query());
    }

    public function testNewEntityValidation(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/A builder for the "test_grid_.+" data grid is not found\./');

        $value = new GridAwareQueryDesignerStub();
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);

        $provider = $this->createMock(BuilderAwareConfigurationProviderStub::class);
        $provider->expects(self::once())
            ->method('isApplicable')
            ->with(self::stringStartsWith(GridAwareQueryDesignerStub::GRID_PREFIX))
            ->willReturn(false);
        $this->configurationProvider->expects(self::once())
            ->method('getProviders')
            ->willReturn([$provider]);

        $this->validator->validate($value, new Query());
    }
}
