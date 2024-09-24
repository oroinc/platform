<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\EntityBundle\Provider\DictionaryEntityDataProvider;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchEnumFilter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEnumFilterType;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SearchEnumFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var DictionaryEntityDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $dictionaryEntityDataProvider;

    /** @var SearchEnumFilter */
    private $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->dictionaryEntityDataProvider = $this->createMock(DictionaryEntityDataProvider::class);

        $this->filter = new SearchEnumFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->dictionaryEntityDataProvider
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
                SearchEnumFilterType::class,
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
                'type' => 'multiselect',
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
                SearchEnumFilterType::class,
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
                'type' => 'multiselect',
                'lazy' => false,
                'class' => $entityClass,
                'initialData' => []
            ],
            $this->filter->getMetadata()
        );
    }

    public function testApplyExceptionForWrongFilterDatasourceAdapter(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply($this->createMock(FilterDatasourceAdapterInterface::class), []);
    }

    public function testApply(): void
    {
        $fieldName = 'field';
        $value = [
            'value1',
            'value2'
        ];

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects(self::once())
            ->method('addRestriction')
            ->with(new Comparison($fieldName, Comparison::IN, $value), FilterUtility::CONDITION_AND, false);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        self::assertTrue($this->filter->apply($ds, ['type' => null, 'value' => $value]));
    }

    public function testPrepareData(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
