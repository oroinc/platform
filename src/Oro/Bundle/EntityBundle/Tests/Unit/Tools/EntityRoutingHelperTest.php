<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EntityRoutingHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $urlGenerator;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlGenerator = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->entityRoutingHelper = new EntityRoutingHelper(
            $this->doctrineHelper,
            $this->urlGenerator
        );
    }

    /**
     * @dataProvider encodeClassNameProvider
     */
    public function testEncodeClassName($src, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityRoutingHelper->encodeClassName($src)
        );
    }

    /**
     * @dataProvider decodeClassNameProvider
     */
    public function testDecodeClassName($src, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityRoutingHelper->decodeClassName($src)
        );
    }

    public function encodeClassNameProvider()
    {
        return [
            ['Acme\Bundle\TestClass', 'Acme_Bundle_TestClass'],
            ['Acme_Bundle_TestClass', 'Acme_Bundle_TestClass'],
        ];
    }

    public function decodeClassNameProvider()
    {
        return [
            ['Acme_Bundle_TestClass', 'Acme\Bundle\TestClass'],
            ['Acme\Bundle\TestClass', 'Acme\Bundle\TestClass'],
            [ExtendHelper::ENTITY_NAMESPACE . 'TestClass', ExtendHelper::ENTITY_NAMESPACE . 'TestClass'],
            [
                str_replace('\\', '_', ExtendHelper::ENTITY_NAMESPACE) . 'TestClass',
                ExtendHelper::ENTITY_NAMESPACE . 'TestClass'
            ],
            [
                str_replace('\\', '_', ExtendHelper::ENTITY_NAMESPACE) . 'Test_Class',
                ExtendHelper::ENTITY_NAMESPACE . 'Test_Class'
            ],
        ];
    }

    public function testGetRouteParameters()
    {
        $this->assertEquals(
            [
                'entityClass' => 'Acme_Bundle_TestClass',
                'entityId'    => '123'
            ],
            $this->entityRoutingHelper->getRouteParameters('Acme\Bundle\TestClass', 123)
        );
    }

    public function testGenerateUrl()
    {
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'test_route',
                [
                    'entityClass' => 'Acme_Bundle_TestClass',
                    'entityId'    => '123',
                    'param1' => 'test'
                ]
            )
            ->will($this->returnValue('test_url'));

        $this->assertEquals(
            'test_url',
            $this->entityRoutingHelper->generateUrl('test_route', 'Acme\Bundle\TestClass', 123, ['param1' => 'test'])
        );
    }

    public function testGenerateUrlByRequest()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['entityClass', null, false, 'Acme\Bundle\TestClass'],
                        ['entityId', null, false, 123],
                    ]
                )
            );

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'test_route',
                [
                    'entityClass' => 'Acme_Bundle_TestClass',
                    'entityId'    => '123',
                    'param1' => 'test'
                ]
            )
            ->will($this->returnValue('test_url'));

        $this->assertEquals(
            'test_url',
            $this->entityRoutingHelper->generateUrlByRequest('test_route', $request, ['param1' => 'test'])
        );
    }

    public function testGenerateUrlByRequestNoEntityClass()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['entityClass', null, false, null],
                    ]
                )
            );

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'test_route',
                [
                    'param1' => 'test'
                ]
            )
            ->will($this->returnValue('test_url'));

        $this->assertEquals(
            'test_url',
            $this->entityRoutingHelper->generateUrlByRequest('test_route', $request, ['param1' => 'test'])
        );
    }

    public function testGetEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with('Acme\Bundle\TestClass', 123)
            ->will($this->returnValue($entity));

        $this->assertSame(
            $entity,
            $this->entityRoutingHelper->getEntity('Acme_Bundle_TestClass', 123)
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Entity class "Acme\Bundle\TestClass" is not manageable.
     */
    public function testGetEntityForNotManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with('Acme\Bundle\TestClass', 123)
            ->will($this->throwException(new NotManageableEntityException('Acme\Bundle\TestClass')));

        $this->entityRoutingHelper->getEntity('Acme_Bundle_TestClass', 123);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Not Found
     */
    public function testGetEntityForNotExistingEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with('Acme\Bundle\TestClass', 123)
            ->will($this->returnValue(null));

        $this->entityRoutingHelper->getEntity('Acme_Bundle_TestClass', 123);
    }

    public function testGetEntityReference()
    {
        $entityReference = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('Acme\Bundle\TestClass', 123)
            ->will($this->returnValue($entityReference));

        $this->assertSame(
            $entityReference,
            $this->entityRoutingHelper->getEntityReference('Acme_Bundle_TestClass', 123)
        );
    }

    public function testGetEntityReferenceForNewEntity()
    {
        $entityReference = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('createEntityInstance')
            ->with('Acme\Bundle\TestClass')
            ->will($this->returnValue($entityReference));

        $this->assertSame(
            $entityReference,
            $this->entityRoutingHelper->getEntityReference('Acme_Bundle_TestClass')
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Entity class "Acme\Bundle\TestClass" is not manageable.
     */
    public function testGetEntityReferenceForNotManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('Acme\Bundle\TestClass', 123)
            ->will($this->throwException(new NotManageableEntityException('Acme\Bundle\TestClass')));

        $this->entityRoutingHelper->getEntityReference('Acme_Bundle_TestClass', 123);
    }


    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Entity class "Acme\Bundle\TestClass" is not manageable.
     */
    public function testGetEntityReferenceForNewNotManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('createEntityInstance')
            ->with('Acme\Bundle\TestClass')
            ->will($this->throwException(new NotManageableEntityException('Acme\Bundle\TestClass')));

        $this->entityRoutingHelper->getEntityReference('Acme_Bundle_TestClass');
    }
}
