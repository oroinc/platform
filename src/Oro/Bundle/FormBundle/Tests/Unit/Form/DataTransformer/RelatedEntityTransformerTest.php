<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__\ItemStubProxy;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\FormBundle\Form\DataTransformer\RelatedEntityTransformer;

class RelatedEntityTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var RelatedEntityTransformer */
    protected $transformer;

    protected function setUp()
    {
        $this->doctrineHelper      = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade      = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transformer = new RelatedEntityTransformer(
            $this->doctrineHelper,
            $this->entityAliasResolver,
            $this->securityFacade
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

        $this->entityAliasResolver->expects($this->never())
            ->method('getClassByAlias');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Test\Entity')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($entity);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(true);

        $this->assertSame($entity, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformByEntityAlias()
    {
        $value  = ['id' => 123, 'entity' => 'alias'];
        $entity = new \stdClass();

        $this->entityAliasResolver->expects($this->once())
            ->method('getClassByAlias')
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
            ->with(123)
            ->willReturn($entity);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(true);

        $this->assertSame($entity, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformNotFound()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityAliasResolver->expects($this->never())
            ->method('getClassByAlias');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Test\Entity')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformEntityException()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityAliasResolver->expects($this->never())
            ->method('getClassByAlias');

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Test\Entity')
            ->will($this->throwException(new NotManageableEntityException('Test\Entity')));

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformNoViewPermissions()
    {
        $value  = ['id' => 123, 'entity' => 'Test\Entity'];
        $entity = new \stdClass();

        $this->entityAliasResolver->expects($this->never())
            ->method('getClassByAlias');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Test\Entity')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($entity);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $this->identicalTo($entity))
            ->willReturn(false);

        $this->assertSame($value, $this->transformer->reverseTransform($value));
    }
}
