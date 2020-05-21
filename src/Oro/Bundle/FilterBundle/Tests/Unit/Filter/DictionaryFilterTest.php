<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEnumValue;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

class DictionaryFilterTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    /** @var MockObject */
    protected $formFactory;

    /** @var DictionaryFilter */
    protected $filter;

    protected function setUp(): void
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

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $doctrine->method('getManagerForClass')->willReturn($this->em);

        $this->filter = new class($this->formFactory, new FilterUtility()) extends DictionaryFilter {
            public function xgetParams(): array
            {
                return $this->params;
            }
        };
    }

    public function testInit()
    {
        $this->filter->init('test', []);
        static::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => []],
            $this->filter->xgetParams()
        );
    }

    public function testInitWithNullValue()
    {
        $this->filter->init('test', ['null_value' => ':empty:']);
        static::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'null_value' => ':empty:', 'options' => []],
            $this->filter->xgetParams()
        );
    }

    public function testInitWithClass()
    {
        $this->filter->init('test', ['class' => 'Test\EnumValue']);
        static::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => ['class' => 'Test\EnumValue']],
            $this->filter->xgetParams()
        );
    }

    public function testInitWithDictionaryCode()
    {
        $this->filter->init('test', ['dictionary_code' => 'test_dictionary']);
        static::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => ['dictionary_code' => 'test_dictionary']],
            $this->filter->xgetParams()
        );
    }

    public function testGetForm()
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(static::once())
            ->method('create')
            ->with(DictionaryFilterType::class)
            ->willReturn($form);

        static::assertSame(
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

        /** @var OrmFilterDatasourceAdapter|MockObject $ds */
        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();
        $ds->method('generateParameterName')->willReturn('param1');

        $this->filter->apply($ds, $data);

        $result = $qb->getQuery()->getDQL();

        static::assertEquals('SELECT o.id FROM Stub:TestEntity o WHERE test IN(:param1)', $result);
        static::assertEquals($values, $qb->getParameter('param1')->getValue());
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

        /** @var OrmFilterDatasourceAdapter|MockObject $ds */
        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();

        $ds->method('generateParameterName')->willReturn('param1');

        $this->filter->apply($ds, $data);

        $result = $qb->getQuery()->getDQL();

        static::assertEquals('SELECT o.id FROM Stub:TestEntity o WHERE test NOT IN(:param1)', $result);
        static::assertEquals($values, $qb->getParameter('param1')->getValue());
    }
}
