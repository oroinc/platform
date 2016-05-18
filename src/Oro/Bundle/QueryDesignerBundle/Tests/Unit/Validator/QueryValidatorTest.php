<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator;

use Doctrine\DBAL\DBALException;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\QueryConstraint;
use Oro\Bundle\QueryDesignerBundle\Validator\QueryValidator;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class QueryValidatorTest extends \PHPUnit_Framework_TestCase
{
    const MESSAGE = 'Invalid query';

    /**
     * @var QueryValidator
     */
    protected $validator;

    /**
     * @var QueryConstraint
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $chainConfigurationProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $gridBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->chainConfigurationProvider = $this->getMock(
            'Oro\Bundle\DataGridBundle\Provider\ChainConfigurationProvider'
        );

        $this->gridBuilder = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Builder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->validator = new QueryValidator(
            $this->chainConfigurationProvider,
            $this->gridBuilder,
            $this->translator,
            false
        );

        $this->context = $this->getMock('\Symfony\Component\Validator\ExecutionContextInterface');
        $this->validator->initialize($this->context);

        $this->constraint = new QueryConstraint();

        $this->translator
            ->expects($this->any())
            ->method('trans')
            ->with($this->equalTo($this->constraint->message))
            ->will($this->returnValue(self::MESSAGE));
    }

    public function testValidateNotMatchedQuery()
    {
        $this->context
            ->expects($this->never())
            ->method('addViolation');

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $datasource
     * @param bool                                     $useOrmDatasource
     * @param \Exception                               $exception
     * @param \Exception                               $configurationException
     * @param int                                      $expectsCount
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate($datasource, $useOrmDatasource, $exception, $configurationException, $expectsCount)
    {
        $provider = $this
            ->getMockBuilder('Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $builder = $this
            ->getMockBuilder('Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $provider
            ->expects($this->once())
            ->method('isApplicable')
            ->will($this->returnValue(true));
        $provider
            ->expects($this->once())
            ->method('getBuilder')
            ->will($this->returnValue($builder));
        $this->chainConfigurationProvider
            ->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue([$provider, new \stdClass()]));

        if ($configurationException) {
            $builder
                ->expects($this->once())
                ->method('getConfiguration')
                ->will($this->throwException($configurationException));
        } else {
            $configuration = $this
                ->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
                ->disableOriginalConstructor()
                ->getMock();
            $builder
                ->expects($this->once())
                ->method('getConfiguration')
                ->will($this->returnValue($configuration));
        }
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $this->gridBuilder
            ->expects($this->exactly($expectsCount))
            ->method('build')
            ->will($this->returnValue($datagrid));
        $datagrid
            ->expects($this->exactly($expectsCount))
            ->method('getDatasource')
            ->will($this->returnValue($datasource));
        $qb = $this
            ->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
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

        $this->validator->validate(new Segment(), $this->constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                $this->getDataSourceInterfaceMock(),
                false,
                new DBALException('failed'),
                null,
                1
            ],
            [
                $this->getDataSourceInterfaceMock(),
                false,
                new InvalidConfigurationException(),
                null,
                1
            ],
            [
                $this->getDataSourceInterfaceMock(),
                false,
                null,
                null,
                1
            ],
            [
                $this->getOrmDataSourceInterfaceMock(),
                true,
                new DBALException('failed'),
                null,
                1
            ],
            [
                $this->getOrmDataSourceInterfaceMock(),
                true,
                new InvalidConfigurationException(),
                null,
                1
            ],
            [
                $this->getOrmDataSourceInterfaceMock(),
                false,
                null,
                new InvalidConfigurationException(),
                0
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataSourceInterfaceMock()
    {
        return $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getOrmDataSourceInterfaceMock()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @expectedException \Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Builder is missing
     */
    public function testBuilderIsMissing()
    {
        $this->chainConfigurationProvider
            ->expects($this->once())
            ->method('getProviders')
            ->will($this->returnValue([]));

        $this->validator->validate(new Segment(), $this->constraint);
    }
}
