<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEntity;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

class DictionaryFilterTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private FormFactoryInterface&MockObject $formFactory;
    private DictionaryFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->filter = new DictionaryFilter($this->formFactory, new FilterUtility());
    }

    public function testInit(): void
    {
        $this->filter->init('test', []);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => []],
            $params
        );
    }

    public function testInitWithNullValue(): void
    {
        $this->filter->init('test', ['null_value' => ':empty:']);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'null_value' => ':empty:', 'options' => []],
            $params
        );
    }

    public function testInitWithClass(): void
    {
        $this->filter->init('test', ['class' => 'Test\EnumValue']);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => ['class' => 'Test\EnumValue']],
            $params
        );
    }

    public function testInitWithDictionaryCode(): void
    {
        $this->filter->init('test', ['dictionary_code' => 'test_dictionary']);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [FilterUtility::FRONTEND_TYPE_KEY => 'dictionary', 'options' => ['dictionary_code' => 'test_dictionary']],
            $params
        );
    }

    public function testGetForm(): void
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(DictionaryFilterType::class)
            ->willReturn($form);

        self::assertSame($form, $this->filter->getForm());
    }

    public function testApply(): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from(TestEntity::class, 'o');

        $values = [
            new TestEnumValue('test', 'Test', 'val1'),
            new TestEnumValue('test', 'Test', 'val2')
        ];
        $data = [
            'value' => $values
        ];

        $params = [
            'null_value'                 => ':empty:',
            FilterUtility::DATA_NAME_KEY => 'o.values'
        ];
        $this->filter->init('test', $params);

        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();
        $ds->expects(self::once())
            ->method('generateParameterName')
            ->willReturn('param1');

        $this->filter->apply($ds, $data);

        $result = $qb->getQuery()->getDQL();

        self::assertEquals('SELECT o.id FROM ' . TestEntity::class . ' o WHERE test IN(:param1)', $result);
        self::assertEquals($values, $qb->getParameter('param1')->getValue());
    }

    public function testApplyNot(): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('o.id')
            ->from(TestEntity::class, 'o');

        $values = [
            new TestEnumValue('test', 'Test', 'val1'),
            new TestEnumValue('test', 'Test', 'val2')
        ];
        $data = [
            'type' => ChoiceFilterType::TYPE_NOT_CONTAINS,
            'value' => $values
        ];

        $params = [
            'null_value'                 => ':empty:',
            FilterUtility::DATA_NAME_KEY => 'o.values'
        ];
        $this->filter->init('test', $params);

        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods(['generateParameterName'])
            ->setConstructorArgs([$qb])
            ->getMock();

        $ds->expects(self::once())
            ->method('generateParameterName')
            ->willReturn('param1');

        $this->filter->apply($ds, $data);

        $result = $qb->getQuery()->getDQL();

        self::assertEquals('SELECT o.id FROM ' . TestEntity::class . ' o WHERE test NOT IN(:param1)', $result);
        self::assertEquals($values, $qb->getParameter('param1')->getValue());
    }

    public function testPrepareData(): void
    {
        $data = [];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
