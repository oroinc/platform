<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchEntityFilter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SearchEntityFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var FilterUtility|\PHPUnit\Framework\MockObject\MockObject */
    protected $filterUtility;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var SearchEntityFilter */
    protected $filter;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filterUtility = $this->createMock(FilterUtility::class);
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->filter = new SearchEntityFilter($this->formFactory, $this->filterUtility, $this->doctrineHelper);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testApplyExceptionForWrongFilterDatasourceAdapter()
    {
        /** @var FilterDatasourceAdapterInterface $datasource */
        $datasource = $this->createMock(FilterDatasourceAdapterInterface::class);

        $this->filter->apply($datasource, []);
    }

    public function testGetMetadata()
    {
        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'field', 'class' => \stdClass::class]);

        $typeFormView = new FormView();
        $typeFormView->vars['choices'] = [];

        $valueFormView = new FormView();
        $valueFormView->vars['choices'] = [];
        $valueFormView->vars['multiple'] = true;

        $formView = new FormView();
        $formView->children['type'] = $typeFormView;
        $formView->children['value'] = $valueFormView;
        $formView->vars['populate_default'] = true;

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                SearchEntityFilterType::class,
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
                'ftype' => 'choice',
                'options' => [
                    'class' => \stdClass::class
                ],
                'lazy' => false,
                'populateDefault' => true,
                'type' => 'multichoice',
            ],
            $this->filter->getMetadata()
        );
    }

    public function testApply()
    {
        $fieldName = 'field';
        $entity1 = $this->getEntity(Item::class, ['id' => 1001]);
        $entity2 = $this->getEntity(Item::class, ['id' => 2002]);
        $entity3 = $this->getEntity(Item::class, ['id' => null]);

        $value = new ArrayCollection([$entity1, $entity2]);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getSingleEntityIdentifier')
            ->withConsecutive(
                [$entity1, false],
                [$entity2, false],
                [$entity3, false]
            )
            ->willReturnOnConsecutiveCalls(
                $entity1->getId(),
                $entity2->getId(),
                $entity3->getId()
            );

        /** @var SearchFilterDatasourceAdapter|\PHPUnit\Framework\MockObject\MockObject $ds */
        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with(
                new Comparison(
                    $fieldName,
                    Comparison::IN,
                    [
                        $entity1->getId(),
                        $entity2->getId()
                    ]
                ),
                FilterUtility::CONDITION_AND,
                false
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => null,
                    'value' => $value,
                ]
            )
        );
    }
}
