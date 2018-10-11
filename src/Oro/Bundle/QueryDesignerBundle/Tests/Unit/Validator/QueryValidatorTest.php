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
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\QueryConstraint;
use Oro\Bundle\QueryDesignerBundle\Validator\QueryValidator;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    protected function setUp()
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

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);

        $this->constraint = new QueryConstraint();

        $this->translator
            ->expects($this->any())
            ->method('trans')
            ->with($this->constraint->message)
            ->will($this->returnValue('Invalid query'));
    }

    public function testValidateNotMatchedQuery()
    {
        $this->context
            ->expects($this->never())
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
        $value = new Segment();
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(123);


        $provider = $this->createMock(ReportDatagridConfigurationProvider::class);
        $builder = $this->createMock(DatagridConfigurationBuilder::class);

        $provider
            ->expects($this->once())
            ->method('isApplicable')
            ->will($this->returnValue(true));
        $provider
            ->expects($this->once())
            ->method('getBuilder')
            ->will($this->returnValue($builder));
        $this->configurationProvider
            ->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue([$provider, new \stdClass()]));

        if ($configurationException) {
            $builder
                ->expects($this->once())
                ->method('getConfiguration')
                ->will($this->throwException($configurationException));
        } else {
            $configuration = $this->createMock(DatagridConfiguration::class);
            $builder
                ->expects($this->once())
                ->method('getConfiguration')
                ->will($this->returnValue($configuration));
        }
        $datagrid = $this->createMock(DatagridInterface::class);

        $this->gridBuilder
            ->expects($this->exactly($expectsCount))
            ->method('build')
            ->will($this->returnValue($datagrid));
        $datagrid
            ->expects($this->exactly($expectsCount))
            ->method('getAcceptedDatasource')
            ->willReturnCallback(function () use ($datasource, $value) {
                $this->validator->initialize($this->createMock(ExecutionContextInterface::class));
                $this->validator->validate($value, $this->constraint);

                return $datasource;
            });
        $qb = $this->createMock(QueryBuilder::class);
        $datasource
            ->expects($this->exactly($expectsCount))
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        if ($useOrmDatasource) {
            $qb
                ->expects($this->exactly($expectsCount))
                ->method('setMaxResults')
                ->will($this->returnSelf());
        }
        if ($exception) {
            $datasource
                ->expects($this->exactly($expectsCount))
                ->method('getResults')
                ->will($this->throwException($exception));
        } else {
            $datasource
                ->expects($this->exactly($expectsCount))
                ->method('getResults')
                ->will($this->returnValue([]));
        }

        if ($exception || $configurationException) {
            $this->context
                ->expects($this->once())
                ->method('addViolation');
        } else {
            $this->context
                ->expects($this->never())
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

    /**
     * @expectedException \Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Builder is missing
     */
    public function testBuilderIsMissing()
    {
        $this->configurationProvider
            ->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue([]));

        $this->validator->validate(new Segment(), $this->constraint);
    }

    /**
     * @expectedException \Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Builder is missing
     */
    public function testExistingEntityValidation()
    {
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->willReturn(1);

        $provider = $this->createMock(ReportDatagridConfigurationProvider::class);

        $provider
            ->expects($this->once())
            ->method('isApplicable')
            ->with(Segment::GRID_PREFIX.'1')
            ->will($this->returnValue(false));

        $this->configurationProvider
            ->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue([$provider]));

        $this->validator->validate(new Segment(), $this->constraint);
    }

    /**
     * @expectedException \Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Builder is missing
     */
    public function testNewEntityValidation()
    {
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->willReturn(null);

        $provider = $this->createMock(ReportDatagridConfigurationProvider::class);

        $provider
            ->expects($this->once())
            ->method('isApplicable')
            ->with($this->stringStartsWith(Segment::GRID_PREFIX))
            ->will($this->returnValue(false));

        $this->configurationProvider
            ->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue([$provider]));

        $this->validator->validate(new Segment(), $this->constraint);
    }
}
