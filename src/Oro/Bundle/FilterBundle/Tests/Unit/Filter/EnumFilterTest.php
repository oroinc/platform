<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\EntityBundle\Provider\DictionaryEntityDataProvider;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EnumFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class EnumFilterTest extends OrmTestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private DictionaryEntityDataProvider&MockObject $dictionaryEntityDataProvider;
    private EnumFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->dictionaryEntityDataProvider = $this->createMock(DictionaryEntityDataProvider::class);

        $this->filter = new EnumFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->dictionaryEntityDataProvider
        );
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

    public function testInitWithEnumCode(): void
    {
        $this->filter->init('test', ['enum_code' => 'test_enum']);

        $params = ReflectionUtil::getPropertyValue($this->filter, 'params');

        self::assertEquals(
            [
                FilterUtility::FRONTEND_TYPE_KEY => 'dictionary',
                'options' => [
                    'enum_code' => 'test_enum',
                    'class' => 'Extend\Entity\EV_Test_Enum'
                ],
                'class' => 'Extend\Entity\EV_Test_Enum'
            ],
            $params
        );
    }

    public function testGetMetadata(): void
    {
        $entityClass = \stdClass::class;
        $ids = ['item1'];
        $initialValues = [['id' => 'item1', 'value' => 'item1', 'text' => 'Item 1']];

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'field', 'class' => $entityClass]);

        $childFormView = new FormView();
        $childFormView->vars['choices'] = [];

        $formView = new FormView();
        $formView->children['type'] = $childFormView;

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $valueFormField = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('value')
            ->willReturn($valueFormField);
        $valueFormField->expects(self::once())
            ->method('getData')
            ->willReturn($ids);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(
                EnumFilterType::class,
                [],
                ['csrf_protection' => false, 'class' => $entityClass]
            )
            ->willReturn($form);

        $this->dictionaryEntityDataProvider->expects(self::once())
            ->method('getValuesByIds')
            ->with($entityClass, $ids)
            ->willReturn($initialValues);

        self::assertEquals(
            [
                'name' => 'test',
                'label' => 'Test',
                'choices' => [],
                'type' => 'dictionary',
                'lazy' => false,
                'class' => $entityClass,
                'initialData' => $initialValues
            ],
            $this->filter->getMetadata()
        );
    }

    public function testGetMetadataWhenNoIds(): void
    {
        $entityClass = \stdClass::class;

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'field', 'class' => $entityClass]);

        $childFormView = new FormView();
        $childFormView->vars['choices'] = [];

        $formView = new FormView();
        $formView->children['type'] = $childFormView;

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);
        $valueFormField = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('value')
            ->willReturn($valueFormField);
        $valueFormField->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(
                EnumFilterType::class,
                [],
                ['csrf_protection' => false, 'class' => $entityClass]
            )
            ->willReturn($form);

        $this->dictionaryEntityDataProvider->expects(self::never())
            ->method('getValuesByIds');

        self::assertEquals(
            [
                'name' => 'test',
                'label' => 'Test',
                'choices' => [],
                'type' => 'dictionary',
                'lazy' => false,
                'class' => $entityClass,
                'initialData' => []
            ],
            $this->filter->getMetadata()
        );
    }

    public function testGetForm(): void
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(EnumFilterType::class)
            ->willReturn($form);

        self::assertSame($form, $this->filter->getForm());
    }

    /**
     * @dataProvider filterProvider
     */
    public function testBuildComparisonExpr(int $filterType, string $expected): void
    {
        $em = $this->getTestEntityManager();
        $qb = $em->createQueryBuilder()
            ->select('o.id')
            ->from('Stub:TestOrder', 'o');

        $ds = $this->getMockBuilder(OrmFilterDatasourceAdapter::class)
            ->onlyMethods([])
            ->setConstructorArgs([$qb])
            ->getMock();

        $qb->where(ReflectionUtil::callMethod(
            $this->filter,
            'buildComparisonExpr',
            [$ds, $filterType, 'o.testField', 'param1']
        ));

        self::assertSame($expected, $qb->getDQL());
    }

    public function filterProvider(): array
    {
        return [
            [
                DictionaryFilterType::TYPE_NOT_IN,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField IS NULL OR o.testField NOT IN(:param1)'
            ],
            [
                DictionaryFilterType::EQUAL,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField = :param1'
            ],
            [
                DictionaryFilterType::NOT_EQUAL,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField IS NULL OR o.testField <> :param1'
            ],
            [
                DictionaryFilterType::TYPE_IN,
                'SELECT o.id FROM Stub:TestOrder o WHERE o.testField IN(:param1)'
            ]
        ];
    }

    public function testPrepareData(): void
    {
        $data = ['key' => 'value'];
        self::assertSame($data, $this->filter->prepareData($data));
    }
}
