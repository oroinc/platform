<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__\ItemStubProxy;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\RelatedEntityTransformer;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RelatedEntityTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityClassNameHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var RelatedEntityTransformer */
    protected $transformer;

    protected function setUp()
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
    public function testTransform($value, $expectedValue)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($value))
            ->willReturnCallback(
                function ($entity) {
                    return $entity->id;
                }
            );

        $this->assertSame($expectedValue, $this->transformer->transform($value));
    }

    public function transformDataProvider()
    {
        return [
            [
                null,
                null
            ],
            [
                new ItemStub(['id' => 123]),
                ['id' => 123, 'entity' => 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub']
            ],
            [
                new ItemStubProxy(['id' => 123]),
                ['id' => 123, 'entity' => 'ItemStubProxy']
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     */
    public function testTransformForNotObject()
    {
        $this->transformer->transform('invalid_value');
    }

    /**
     * @dataProvider reverseTransformForEmptyValueDataProvider
     */
    public function testReverseTransformForEmptyValue($value)
    {
        $this->assertNull($this->transformer->reverseTransform($value));
    }

    public function reverseTransformForEmptyValueDataProvider()
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
    public function testReverseTransformForNonTransformableValue($value)
    {
        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformForNonTransformableValueDataProvider()
    {
        return [
            [new \stdClass()],
            [['id' => 123]],
            [['entity' => 'Test\Entity']],
        ];
    }

    public function testReverseTransform()
    {
        $value  = ['id' => 123, 'entity' => 'Test\Entity'];
        $entity = new \stdClass();

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
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

    public function testReverseTransformByEntityAlias()
    {
        $value  = ['id' => 123, 'entity' => 'alias'];
        $entity = new \stdClass();

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with('alias')
            ->willReturn('Test\Entity');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
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

    public function testReverseTransformNotFound()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
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

    public function testReverseTransformEntityException()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($value['entity'])
            ->will($this->throwException(new NotManageableEntityException($value['entity'])));

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformNoViewPermissions()
    {
        $value  = ['id' => 123, 'entity' => 'Test\Entity'];
        $entity = new \stdClass();

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
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
