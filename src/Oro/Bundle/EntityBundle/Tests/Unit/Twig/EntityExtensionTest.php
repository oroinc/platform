<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__\ItemStubProxy;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityBundle\Twig\EntityExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var EntityIdAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $entityIdAccessor;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var EntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityAliasResolver;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityFallbackResolver;

    /** @var EntityExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->entityIdAccessor = $this->createMock(EntityIdAccessor::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);

        $container = self::getContainerBuilder()
            ->add(EntityIdAccessor::class, $this->entityIdAccessor)
            ->add(EntityRoutingHelper::class, $this->entityRoutingHelper)
            ->add(EntityNameResolver::class, $this->entityNameResolver)
            ->add(EntityAliasResolver::class, $this->entityAliasResolver)
            ->add(DoctrineHelper::class, $this->doctrineHelper)
            ->add(EntityFallbackResolver::class, $this->entityFallbackResolver)
            ->getContainer($this);

        $this->extension = new EntityExtension($container);
    }

    /**
     * @dataProvider getClassNameDataProvider
     */
    public function testGetClassName(?string $expectedClass, mixed $object)
    {
        $this->entityRoutingHelper->expects($this->never())
            ->method('getUrlSafeClassName');

        $this->assertEquals(
            $expectedClass,
            self::callTwigFunction($this->extension, 'oro_class_name', [$object])
        );
    }

    public function getClassNameDataProvider(): array
    {
        return [
            'null' => [
                'expectedClass' => null,
                'object' => null,
            ],
            'not an object' => [
                'expectedClass' => null,
                'object' => 'string',
            ],
            'object' => [
                'expectedClass' => ItemStub::class,
                'object' => new ItemStub(),
            ],
            'proxy' => [
                'expectedClass' => 'ItemStubProxy',
                'object' => new ItemStubProxy(),
            ],
        ];
    }

    public function testGetClassNameEscaped()
    {
        $object = new ItemStub();
        $class = get_class($object);
        $expectedClass = str_replace('\\', '_', $class);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with($class)
            ->willReturn($expectedClass);

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
        $object = new ItemStub();
        $class = get_class($object);
        $expectedClass = str_replace('\\', '_', $class);
        $objectId = 123;
        $action = 'test';

        $expected = ['some_val' => 'val'];

        $this->entityIdAccessor->expects($this->once())
            ->method('getIdentifier')
            ->with($this->identicalTo($object))
            ->willReturn($objectId);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with($class)
            ->willReturn($expectedClass);
        $this->entityRoutingHelper->expects($this->once())
            ->method('getRouteParameters')
            ->with($expectedClass, $objectId, $action)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_action_params', [$object, $action])
        );
    }

    public function testGetEntityName()
    {
        $entity = new \stdClass();
        $locale = 'fr_CA';
        $expectedResult = 'John Doe';

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($entity), null, $locale)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_name', [$entity, $locale])
        );
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

    public function testGetFallbackValue()
    {
        $className = ItemStub::class;
        $fieldName = 'test';

        $this->entityFallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->with($className, $fieldName)
            ->willReturn('value');

        $this->assertEquals(
            'value',
            self::callTwigFunction($this->extension, 'oro_entity_fallback_value', [$className, $fieldName])
        );
    }

    public function testGetFallbackType()
    {
        $className = ItemStub::class;
        $fieldName = 'test';

        $this->entityFallbackResolver->expects($this->once())
            ->method('getType')
            ->with($className, $fieldName)
            ->willReturn('integer');

        $this->assertEquals(
            'integer',
            self::callTwigFunction($this->extension, 'oro_entity_fallback_type', [$className, $fieldName])
        );
    }

    public function testGetEntityReference()
    {
        $className = ItemStub::class;
        $id = 1;
        $reference =  new ItemStub();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($className, $id)
            ->willReturn($reference);

        $this->assertEquals(
            $reference,
            self::callTwigFunction($this->extension, 'oro_entity_reference', [$className, $id])
        );
    }
}
