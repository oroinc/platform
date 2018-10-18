<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmManyRelationBuilder;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEnumValue;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class DictionaryTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var DictionaryFilter */
    protected $filter;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Stub' => 'Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures'
            ]
        );

        $this->formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $manyRelationBuilder = new ManyRelationBuilder();
        $manyRelationBuilder->addBuilder(new OrmManyRelationBuilder($doctrine));

        $this->filter = new DictionaryFilter(
            $this->formFactory,
            new FilterUtility(),
            $manyRelationBuilder
        );
    }

    public function testInit()
    {
        $params = [];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options'                        => []
            ],
            'params',
            $this->filter
        );
    }

    public function testInitWithNullValue()
    {
        $params = [
            'null_value' => ':empty:'
        ];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'null_value'                     => ':empty:',
                'options'                        => []
            ],
            'params',
            $this->filter
        );
    }

    public function testInitWithClass()
    {
        $params = [
            'class' => 'Test\EnumValue'
        ];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options'                        => [
                    'class' => 'Test\EnumValue'
                ]
            ],
            'params',
            $this->filter
        );
    }

    public function testInitWithDictionaryCode()
    {
        $params = [
            'dictionary_code' => 'test_dictionary'
        ];
        $this->filter->init('test', $params);
        $this->assertAttributeEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options'                        => [
                    'dictionary_code' => 'test_dictionary'
                ]
            ],
            'params',
            $this->filter
        );
    }

    public function testGetForm()
    {
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(DictionaryFilterType::class)
            ->will($this->returnValue($form));

        $this->assertSame(
            $form,
            $this->filter->getForm()
        );
    }

    public function testApply()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestEntity', 'o');

        $values = [
            new TestEnumValue('val1', 'Value1'),
            new TestEnumValue('val2', 'Value2')
        ];
        $data   = [
            'value' => $values
        ];

        $params = [
            'null_value'                 => ':empty:',
            FilterUtility::DATA_NAME_KEY => 'o.values'
        ];
        $this->filter->init('test', $params);

        /** @var OrmFilterDatasourceAdapter|\PHPUnit\Framework\MockObject\MockObject $ds */
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->setMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();

        $ds->expects($this->any())
            ->method('generateParameterName')
            ->will($this->returnValue('param1'));

        $this->filter->apply($ds, $data);

        $result = $qb->getQuery()->getDQL();
        $this->assertEquals(
            'SELECT o.id FROM Stub:TestEntity o WHERE test IN(:param1)',
            $result
        );
        $this->assertEquals(
            $values,
            $qb->getParameter('param1')->getValue()
        );
    }

    public function testApplyNot()
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestEntity', 'o');

        $values = [
            new TestEnumValue('val1', 'Value1'),
            new TestEnumValue('val2', 'Value2')
        ];
        $data   = [
            'type'  => ChoiceFilterType::TYPE_NOT_CONTAINS,
            'value' => $values
        ];

        $params = [
            'null_value'                 => ':empty:',
            FilterUtility::DATA_NAME_KEY => 'o.values'
        ];
        $this->filter->init('test', $params);

        /** @var OrmFilterDatasourceAdapter|\PHPUnit\Framework\MockObject\MockObject $ds */
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->setMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();

        $ds->expects($this->any())
            ->method('generateParameterName')
            ->will($this->returnValue('param1'));

        $this->filter->apply($ds, $data);

        $result = $qb->getQuery()->getDQL();
        $this->assertEquals(
            'SELECT o.id FROM Stub:TestEntity o WHERE test NOT IN(:param1)',
            $result
        );
        $this->assertEquals(
            $values,
            $qb->getParameter('param1')->getValue()
        );
    }
}
