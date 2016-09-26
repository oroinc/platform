<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
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
        $entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassNameHelper = new EntityClassNameHelper($entityAliasResolver);

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlGenerator   = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $this->entityRoutingHelper = new EntityRoutingHelper(
            $entityClassNameHelper,
            $this->doctrineHelper,
            $this->urlGenerator
        );
    }

    /**
     * @dataProvider getUrlSafeClassNameProvider
     */
    public function testGetUrlSafeClassName($src, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityRoutingHelper->getUrlSafeClassName($src)
        );
    }

    /**
     * @dataProvider resolveEntityClassProvider
     */
    public function testResolveEntityClass($src, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->entityRoutingHelper->resolveEntityClass($src)
        );
    }

    public function getUrlSafeClassNameProvider()
    {
        return [
            ['Acme\Bundle\TestClass', 'Acme_Bundle_TestClass'],
            ['Acme_Bundle_TestClass', 'Acme_Bundle_TestClass'],
        ];
    }

    public function resolveEntityClassProvider()
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

    public function testGetAction()
    {
        $action  = 'test';
        $request = new Request([EntityRoutingHelper::PARAM_ACTION => $action]);
        $this->assertEquals(
            $action,
            $this->entityRoutingHelper->getAction($request)
        );
    }

    public function testGetActionNotSpecified()
    {
        $request = new Request();
        $this->assertNull($this->entityRoutingHelper->getAction($request));
    }

    public function testGetEntityClassName()
    {
        $request = new Request([EntityRoutingHelper::PARAM_ENTITY_CLASS => 'Acme_Bundle_TestClass']);
        $this->assertEquals(
            'Acme\Bundle\TestClass',
            $this->entityRoutingHelper->getEntityClassName($request)
        );
    }

    public function testGetEntityClassNameWithOtherParamName()
    {
        $paramName = 'some_entity';
        $request   = new Request([$paramName => 'Acme_Bundle_TestClass']);
        $this->assertEquals(
            'Acme\Bundle\TestClass',
            $this->entityRoutingHelper->getEntityClassName($request, $paramName)
        );
    }

    public function testGetEntityClassNameNotSpecified()
    {
        $request = new Request();
        $this->assertNull($this->entityRoutingHelper->getEntityClassName($request));
    }

    public function testGetEntityId()
    {
        $request = new Request([EntityRoutingHelper::PARAM_ENTITY_ID => '123']);
        $this->assertEquals(
            '123',
            $this->entityRoutingHelper->getEntityId($request)
        );
    }

    public function testGetEntityIdWithOtherParamName()
    {
        $paramName = 'some_entity';
        $request   = new Request([$paramName => '123']);
        $this->assertEquals(
            '123',
            $this->entityRoutingHelper->getEntityId($request, $paramName)
        );
    }

    public function testGetEntityIdNotSpecified()
    {
        $request = new Request();
        $this->assertNull($this->entityRoutingHelper->getEntityId($request));
    }

    public function testGetRouteParameters()
    {
        $this->assertEquals(
            [
                EntityRoutingHelper::PARAM_ENTITY_CLASS => 'Acme_Bundle_TestClass',
                EntityRoutingHelper::PARAM_ENTITY_ID    => '123',
                EntityRoutingHelper::PARAM_ACTION       => 'test'
            ],
            $this->entityRoutingHelper->getRouteParameters('Acme\Bundle\TestClass', 123, 'test')
        );
    }

    public function testGetRouteParametersWithoutAction()
    {
        $this->assertEquals(
            [
                EntityRoutingHelper::PARAM_ENTITY_CLASS => 'Acme_Bundle_TestClass',
                EntityRoutingHelper::PARAM_ENTITY_ID    => '123'
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
                    EntityRoutingHelper::PARAM_ENTITY_CLASS => 'Acme_Bundle_TestClass',
                    EntityRoutingHelper::PARAM_ENTITY_ID    => '123',
                    'param1'                                => 'test'
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
        $request = new Request(
            [
                EntityRoutingHelper::PARAM_ENTITY_CLASS => 'Acme_Bundle_TestClass',
                EntityRoutingHelper::PARAM_ENTITY_ID    => 123
            ]
        );

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'test_route',
                [
                    EntityRoutingHelper::PARAM_ENTITY_CLASS => 'Acme_Bundle_TestClass',
                    EntityRoutingHelper::PARAM_ENTITY_ID    => '123',
                    'param1'                                => 'test'
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
        $request = new Request();

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
     * @expectedExceptionMessage Record doesn't exist
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
