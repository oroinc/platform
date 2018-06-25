<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__\ItemStubProxy;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityBundle\Twig\EntityExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class EntityExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityIdAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityRoutingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityAliasResolver;

    /** @var EntityExtension */
    protected $extension;

    protected function setUp()
    {
        $this->entityIdAccessor = $this->getMockBuilder(EntityIdAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRoutingHelper = $this->getMockBuilder(EntityRoutingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityAliasResolver = $this->getMockBuilder(EntityAliasResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_entity.entity_identifier_accessor', $this->entityIdAccessor)
            ->add('oro_entity.routing_helper', $this->entityRoutingHelper)
            ->add('oro_entity.entity_name_resolver', $this->entityNameResolver)
            ->add('oro_entity.entity_alias_resolver', $this->entityAliasResolver)
            ->getContainer($this);

        $this->extension = new EntityExtension($container);
    }

    protected function tearDown()
    {
        unset($this->extension);
    }

    /**
     * @param string $expectedClass
     * @param mixed  $object
     *
     * @dataProvider getClassNameDataProvider
     */
    public function testGetClassName($expectedClass, $object)
    {
        $this->entityRoutingHelper->expects($this->never())
            ->method('getUrlSafeClassName');

        $this->assertEquals(
            $expectedClass,
            self::callTwigFunction($this->extension, 'oro_class_name', [$object])
        );
    }

    public function getClassNameDataProvider()
    {
        return [
            'null'          => [
                'expectedClass' => null,
                'object'        => null,
            ],
            'not an object' => [
                'expectedClass' => null,
                'object'        => 'string',
            ],
            'object'        => [
                'expectedClass' => 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub',
                'object'        => new ItemStub(),
            ],
            'proxy'         => [
                'expectedClass' => 'ItemStubProxy',
                'object'        => new ItemStubProxy(),
            ],
        ];
    }

    public function testGetClassNameEscaped()
    {
        $object        = new ItemStub();
        $class         = get_class($object);
        $expectedClass = str_replace('\\', '_', $class);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with($class)
            ->will($this->returnValue($expectedClass));

        $this->assertEquals(
            $expectedClass,
            self::callTwigFunction($this->extension, 'oro_class_name', [$object, true])
        );
    }

    public function testGetActionParamsNull()
    {
        $this->assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_action_params', [null])
        );
    }

    public function testGetActionParamsNonObject()
    {
        $this->assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_action_params', ['string'])
        );
    }

    public function testGetActionParams()
    {
        $object        = new ItemStub();
        $class         = get_class($object);
        $expectedClass = str_replace('\\', '_', $class);
        $objectId      = 123;
        $action        = 'test';

        $expected = ['some_val' => 'val'];

        $this->entityIdAccessor->expects($this->once())
            ->method('getIdentifier')
            ->with($this->identicalTo($object))
            ->will($this->returnValue($objectId));

        $this->entityRoutingHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with($class)
            ->will($this->returnValue($expectedClass));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getRouteParameters')
            ->with($expectedClass, $objectId, $action)
            ->will($this->returnValue($expected));

        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_action_params', [$object, $action])
        );
    }

    public function testGetEntityName()
    {
        $entity         = new \stdClass();
        $locale         = 'fr_CA';
        $expectedResult = 'John Doe';

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($entity), null, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_name', [$entity, $locale])
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_entity', $this->extension->getName());
    }

    public function testGetUrlClassName()
    {
        $originalClass = 'Test\\Class';
        $urlSafeClass = 'Test_Class';

        $this->entityRoutingHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with($originalClass)
            ->willReturn($urlSafeClass);

        $this->assertEquals(
            $urlSafeClass,
            self::callTwigFunction($this->extension, 'oro_url_class_name', [$originalClass])
        );
    }
}
