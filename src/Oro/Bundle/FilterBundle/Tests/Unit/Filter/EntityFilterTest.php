<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEntity;
use Symfony\Component\Form\FormFactoryInterface;

class EntityFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new EntityFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine
        );
    }

    public function testPrepareDataWithoutValue()
    {
        $data = [];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNullValue()
    {
        $data = ['value' => null];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdValueAndNoEntityClass()
    {
        $data = ['value' => 123];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', []);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdsValueAndNoEntityClass()
    {
        $data = ['value' => [123]];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', []);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdValueForNonManageableEntity()
    {
        $data = ['value' => 123];

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn(null);

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame(['value' => null], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdsValueForNonManageableEntity()
    {
        $data = ['value' => [123]];

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn(null);

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame(['value' => []], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdValue()
    {
        $data = ['value' => 123];

        $entity = $this->createMock(TestEntity::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getReference')
            ->with(TestEntity::class, $data['value'])
            ->willReturn($entity);

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame(['value' => $entity], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdsValue()
    {
        $data = ['value' => [123]];

        $entity = $this->createMock(TestEntity::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getReference')
            ->with(TestEntity::class, $data['value'][0])
            ->willReturn($entity);

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame(['value' => [$entity]], $this->filter->prepareData($data));
    }
}
