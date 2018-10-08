<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchEnumFilter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEnumFilterType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

abstract class AbstractSearchEnumFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var FilterUtility|\PHPUnit\Framework\MockObject\MockObject */
    protected $filterUtility;

    /** @var DictionaryApiEntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $dictionaryManager;

    /** @var SearchEnumFilter */
    protected $filter;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filterUtility = $this->createMock(FilterUtility::class);
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $this->dictionaryManager = $this->createMock(DictionaryApiEntityManager::class);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testApplyExceptionForWrongFilterDatasourceAdapter()
    {
        $this->filter->apply($this->createMock(FilterDatasourceAdapterInterface::class), []);
    }

    public function testGetMetadata()
    {
        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'field', 'class' => \stdClass::class]);

        $childFormView = new FormView();
        $childFormView->vars['choices'] = [];

        $formView = new FormView();
        $formView->children['type'] = $childFormView;

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->once())
            ->method('get')
            ->with('value')
            ->willReturn($this->createMock(FormInterface::class));

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                SearchEnumFilterType::class,
                [],
                ['csrf_protection' => false, 'class' => \stdClass::class]
            )
            ->willReturn($form);

        $this->assertEquals(
            [
                'name' => 'test',
                'label' => 'Test',
                'choices' => [],
                'data_name' => 'field',
                'ftype' => 'multiselect',
                'options' => [
                    'class' => \stdClass::class
                ],
                'lazy' => false,
                'class' => 'stdClass',
                'initialData' => null
            ],
            $this->filter->getMetadata()
        );
    }
}
