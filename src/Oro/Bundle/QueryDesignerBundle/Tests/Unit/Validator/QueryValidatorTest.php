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
     * @param int                                      $expects
     * @param \Exception                               $exception
     * @param \Exception                               $configurationException
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate($datasource, $useOrmDatasource, $expects, $exception, $configurationException)
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
            ->expects($this->exactly($expects))
            ->method('build')
            ->will($this->returnValue($datagrid));
        $datagrid
            ->expects($this->exactly($expects))
            ->method('getDatasource')
            ->will($this->returnValue($datasource));
        $qb = $this
            ->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource
            ->expects($this->exactly($expects))
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        if ($useOrmDatasource) {
            $qb
                ->expects($this->exactly($expects))
                ->method('setMaxResults')
                ->will($this->returnSelf());
        }
        if ($exception) {
            $datasource
                ->expects($this->exactly($expects))
                ->method('getResults')
                ->will($this->throwException($exception));
        } else {
            $datasource
                ->expects($this->exactly($expects))
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
                1,
                DBALException::driverExceptionDuringQuery(new \Exception('failed'), 'sql'),
                null
            ],
            [
                $this->getDataSourceInterfaceMock(),
                false,
                1,
                new InvalidConfigurationException(),
                null
            ],
            [
                $this->getDataSourceInterfaceMock(),
                false,
                1,
                null,
                null
            ],
            [
                $this->getOrmDataSourceInterfaceMock(),
                true,
                1,
                DBALException::driverExceptionDuringQuery(new \Exception('failed'), 'sql'),
                null
            ],
            [
                $this->getOrmDataSourceInterfaceMock(),
                true,
                1,
                new InvalidConfigurationException(),
                null
            ],
            [
                $this->getOrmDataSourceInterfaceMock(),
                false,
                0,
                null,
                new InvalidConfigurationException()
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataSourceInterfaceMock()
    {
        return $this->getMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
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
