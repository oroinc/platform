<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchEntityFilter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Exception\UnexpectedTypeException;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SearchEntityFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SearchEntityFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new SearchEntityFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine
        );
    }

    public function testApplyExceptionForWrongFilterDatasourceAdapter()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply($this->createMock(FilterDatasourceAdapterInterface::class), []);
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

        $value = new ArrayCollection([$entity1, $entity2, $entity3]);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(3))
            ->method('getClassMetadata')
            ->with(Item::class)
            ->willReturn($classMetadata);
        $this->doctrine->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->with(Item::class)
            ->willReturn($em);
        $classMetadata->expects($this->exactly(3))
            ->method('getIdentifierValues')
            ->withConsecutive(
                [$entity1],
                [$entity2],
                [$entity3]
            )
            ->willReturnOnConsecutiveCalls(
                ['id' => $entity1->getId()],
                ['id' => $entity2->getId()],
                ['id' => $entity3->getId()]
            );

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

        $this->assertTrue($this->filter->apply($ds, ['type' => null, 'value' => $value]));
    }

    public function testPrepareData()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
