<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\TestEntity;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityIdentifierTypeTest extends FormIntegrationTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EntitiesToIdsTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $entitiesToIdsTransformer;

    /** @var EntityIdentifierType */
    private $type;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entitiesToIdsTransformer = $this->createMock(EntitiesToIdsTransformer::class);

        $this->type = $this->getMockBuilder(EntityIdentifierType::class)
            ->onlyMethods(['createEntitiesToIdsTransformer'])
            ->setConstructorArgs([$this->doctrine])
            ->getMock();

        $this->type->expects($this->any())
            ->method('createEntitiesToIdsTransformer')
            ->willReturn($this->entitiesToIdsTransformer);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                EntityIdentifierType::class => $this->type
            ], [])
        ];
    }

    private function createEntityList(string $property, array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $entity = new TestEntity();
            ReflectionUtil::setPropertyValue($entity, $property, $value);

            $result[] = $entity;
        }

        return $result;
    }

    public function testBindDataDefault()
    {
        $value = '1,2,3,4';
        $entities = $this->createEntityList('id', [1, 2, 3, 4]);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($this->entityManager);
        $this->entitiesToIdsTransformer->expects($this->exactly(2))
            ->method('transform')
            ->withConsecutive([$this->isNull()], [$this->identicalTo($entities)])
            ->willReturnOnConsecutiveCalls([], [1, 2, 3, 4]);
        $this->entitiesToIdsTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with([1, 2, 3, 4])
            ->willReturn($entities);

        $form = $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class]
        );

        $form->submit($value);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($entities, $form->getData());

        $view = $form->createView();
        $this->assertSame($value, $view->vars['value']);
    }

    public function testBindDataAcceptArray()
    {
        $value = [1, 2, 3, 4];
        $entities = $this->createEntityList('id', [1, 2, 3, 4]);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($this->entityManager);
        $this->entitiesToIdsTransformer->expects($this->exactly(2))
            ->method('transform')
            ->withConsecutive([$this->isNull()], [$this->identicalTo($entities)])
            ->willReturnOnConsecutiveCalls([], [1, 2, 3, 4]);
        $this->entitiesToIdsTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with([1, 2, 3, 4])
            ->willReturn($entities);

        $form = $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class]
        );

        $form->submit($value);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($entities, $form->getData());

        $view = $form->createView();
        $this->assertSame('1,2,3,4', $view->vars['value']);
    }

    public function testBindDataWithCustomEntityManagerName()
    {
        $value = '1,2,3,4';
        $entities = $this->createEntityList('id', [1, 2, 3, 4]);

        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('custom_entity_manager')
            ->willReturn($this->entityManager);
        $this->entitiesToIdsTransformer->expects($this->exactly(2))
            ->method('transform')
            ->withConsecutive([$this->isNull()], [$this->identicalTo($entities)])
            ->willReturnOnConsecutiveCalls([], [1, 2, 3, 4]);
        $this->entitiesToIdsTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with([1, 2, 3, 4])
            ->willReturn($entities);

        $form = $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class, 'em' => 'custom_entity_manager']
        );

        $form->submit($value);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($entities, $form->getData());

        $view = $form->createView();
        $this->assertSame($value, $view->vars['value']);
    }

    public function testBindDataWithCustomEntityManagerObject()
    {
        $value = '1,2,3,4';
        $entities = $this->createEntityList('id', [1, 2, 3, 4]);

        $this->doctrine->expects($this->never())
            ->method($this->anything());
        $this->entitiesToIdsTransformer->expects($this->exactly(2))
            ->method('transform')
            ->withConsecutive([$this->isNull()], [$this->identicalTo($entities)])
            ->willReturnOnConsecutiveCalls([], [1, 2, 3, 4]);
        $this->entitiesToIdsTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with([1, 2, 3, 4])
            ->willReturn($entities);

        $form = $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class, 'em' => $this->entityManager]
        );

        $form->submit($value);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($entities, $form->getData());

        $view = $form->createView();
        $this->assertSame($value, $view->vars['value']);
    }

    public function testBindDataWithCustomQueryBuilderCallback()
    {
        $value = '1,2,3,4';
        $entities = $this->createEntityList('id', [1, 2, 3, 4]);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($this->entityManager);
        $this->entitiesToIdsTransformer->expects($this->exactly(2))
            ->method('transform')
            ->withConsecutive([$this->isNull()], [$this->identicalTo($entities)])
            ->willReturnOnConsecutiveCalls([], [1, 2, 3, 4]);
        $this->entitiesToIdsTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with([1, 2, 3, 4])
            ->willReturn($entities);

        $form = $this->factory->create(
            EntityIdentifierType::class,
            null,
            [
                'class' => TestEntity::class,
                'queryBuilder' => function ($repository, array $ids) {
                    $result = $repository->createQueryBuilder('o');
                    $result->where('o.id IN (:values)')->setParameter('values', $ids);

                    return $result;
                }
            ]
        );

        $form->submit($value);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($entities, $form->getData());

        $view = $form->createView();
        $this->assertSame($value, $view->vars['value']);
    }

    public function testCreateWhenCannotResolveEntityManagerByClass()
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage(sprintf(
            'Class "%s" is not a managed Doctrine entity. Did you forget to map it?',
            TestEntity::class
        ));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn(null);

        $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class]
        );
    }

    public function testCreateWhenCannotResolveEntityManagerByName()
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage(sprintf(
            'Class "%s" is not a managed Doctrine entity. Did you forget to map it?',
            TestEntity::class
        ));

        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('custom_entity_manager')
            ->willReturn(null);

        $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class, 'em' => 'custom_entity_manager']
        );
    }

    public function testCreateWhenEntityManagerIsInvalid()
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Option "em" should be a string or entity manager object, stdClass given');

        $this->doctrine->expects($this->never())
            ->method($this->anything());

        $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class, 'em' => new \stdClass()]
        );
    }

    public function testCreateWhenQueryBuilderIsInvalid()
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Option "queryBuilder" should be a callable, string given');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($this->entityManager);

        $this->factory->create(
            EntityIdentifierType::class,
            null,
            ['class' => TestEntity::class, 'queryBuilder' => 'invalid']
        );
    }

    /**
     * @dataProvider multipleTypeDataProvider
     */
    public function testCreateEntitiesToIdsTransformer(bool $isMultiple)
    {
        $options = [
            'em' => $this->entityManager,
            'multiple' => $isMultiple,
            'class' => TestEntity::class,
            'property' => 'id',
            'queryBuilder' => function ($repository, array $ids) {
                return $repository->createQueryBuilder('o')->where('o.id IN (:values)')->setParameter('values', $ids);
            },
            'values_delimiter' => ','
        ];
        $builder = $this->createMock(FormBuilderInterface::class);

        $viewTransformer = $this->createMock(
            $isMultiple ? EntitiesToIdsTransformer::class : EntityToIdTransformer::class
        );

        if ($isMultiple) {
            $builder->expects(self::exactly(2))
                ->method('addViewTransformer')
                ->withConsecutive(
                    [$this->identicalTo($viewTransformer)],
                    [$this->isInstanceOf(ArrayToStringTransformer::class)]
                )
                ->willReturnSelf();
        } else {
            $builder->expects(self::once())
                ->method('addViewTransformer')
                ->with($this->identicalTo($viewTransformer))
                ->willReturnSelf();
        }

        $this->type = $this->getMockBuilder(EntityIdentifierType::class)
            ->setConstructorArgs([$this->doctrine])
            ->onlyMethods(['createEntitiesToIdsTransformer'])
            ->getMock();

        $this->type->expects(self::once())
            ->method('createEntitiesToIdsTransformer')
            ->with($options)
            ->willReturn($viewTransformer);

        $this->type->buildForm($builder, $options);
    }

    public function multipleTypeDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
