<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__\ItemStubProxy;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\RelatedEntityTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RelatedEntityTransformerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityClassNameHelper&MockObject $entityClassNameHelper;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private RelatedEntityTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->transformer = new RelatedEntityTransformer(
            $this->doctrineHelper,
            $this->entityClassNameHelper,
            $this->authorizationChecker
        );
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?object $value, ?array $expectedValue): void
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($value))
            ->willReturnCallback(function ($entity) {
                return $entity->id;
            });

        $this->assertSame($expectedValue, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            [
                null,
                null
            ],
            [
                new ItemStub(['id' => 123]),
                ['id' => 123, 'entity' => ItemStub::class]
            ],
            [
                new ItemStubProxy(['id' => 123]),
                ['id' => 123, 'entity' => 'ItemStubProxy']
            ]
        ];
    }

    public function testTransformForNotObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->transformer->transform('invalid_value');
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue(mixed $value): void
    {
        $this->assertNull($this->transformer->reverseTransform($value));
    }

    public function reverseTransformForEmptyValueDataProvider(): array
    {
        return [
            [null],
            [''],
            [[]]
        ];
    }

    /**
     * @dataProvider reverseTransformForNonTransformableValueDataProvider
     */
    public function testReverseTransformForNonTransformableValue(mixed $value): void
    {
        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformForNonTransformableValueDataProvider(): array
    {
        return [
            [new \stdClass()],
            [['id' => 123]],
            [['entity' => 'Test\Entity']],
        ];
    }

    public function testReverseTransform(): void
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];
        $entity = new \stdClass();

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($value['entity'])
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($value['id'])
            ->willReturn($entity);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(true);

        $this->assertSame($entity, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformByEntityAlias(): void
    {
        $value = ['id' => 123, 'entity' => 'alias'];
        $entity = new \stdClass();

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with('alias')
            ->willReturn('Test\Entity');

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Test\Entity')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($value['id'])
            ->willReturn($entity);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(true);

        $this->assertSame($entity, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformNotFound(): void
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($value['entity'])
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($value['id'])
            ->willReturn(null);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformEntityException(): void
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($value['entity'])
            ->willThrowException(new NotManageableEntityException($value['entity']));

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformNoViewPermissions(): void
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];
        $entity = new \stdClass();

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($value['entity'])
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($value['id'])
            ->willReturn($entity);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(false);

        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }
}
