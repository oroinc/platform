<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEntity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class EntityFilterTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private ManagerRegistry&MockObject $doctrine;
    private EntityFilter $filter;

    #[\Override]
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

    public function testPrepareDataWithoutValue(): void
    {
        $data = [];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithNullValue(): void
    {
        $data = ['value' => null];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdValueAndNoEntityClass(): void
    {
        $data = ['value' => 123];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', []);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdsValueAndNoEntityClass(): void
    {
        $data = ['value' => [123]];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->filter->init('entity', []);
        self::assertSame($data, $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdValueForNonManageableEntity(): void
    {
        $data = ['value' => 123];

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn(null);

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame(['value' => null], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdsValueForNonManageableEntity(): void
    {
        $data = ['value' => [123]];

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn(null);

        $this->filter->init('entity', ['options' => ['field_options' => ['class' => TestEntity::class]]]);
        self::assertSame(['value' => []], $this->filter->prepareData($data));
    }

    public function testPrepareDataWithEntityIdValue(): void
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

    public function testPrepareDataWithEntityIdsValue(): void
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
