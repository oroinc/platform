<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityNameResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var EntityNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var EntityNameResolver */
    private $entityNameResolver;

    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(EntityNameProviderInterface::class);
        $this->provider2 = $this->createMock(EntityNameProviderInterface::class);

        $this->entityNameResolver = $this->getEntityNameResolver(
            [$this->provider2, $this->provider1],
            'full',
            [
                'full'  => ['fallback' => 'short'],
                'short' => ['fallback' => null]
            ]
        );
    }

    private function getEntityNameResolver(array $providers, string $defaultFormat, array $config): EntityNameResolver
    {
        $configProvider = $this->createMock(EntityConfigurationProvider::class);
        $configProvider->expects(self::any())
            ->method('getConfiguration')
            ->with(EntityConfiguration::ENTITY_NAME_FORMATS)
            ->willReturn($config);

        return new EntityNameResolver($providers, $defaultFormat, $configProvider);
    }

    public function testGetNameForUndefinedFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The unknown representation format "other".');

        $this->entityNameResolver->getName(new \stdClass(), 'other');
    }

    public function testGetNameForNullEntity()
    {
        $this->provider1->expects($this->never())
            ->method('getName');
        $this->provider2->expects($this->never())
            ->method('getName');

        $this->assertNull($this->entityNameResolver->getName(null, 'full'));
    }

    public function testGetNameWhenRequestedFormatImplementedByRegisteredProviders()
    {
        $entity = new \stdClass();
        $format = 'full';
        $locale = 'en_US';
        $expected = 'EntityName';

        $this->provider1->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn($expected);

        $this->provider2->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn(false);

        $result = $this->entityNameResolver->getName($entity, $format, $locale);
        $this->assertEquals($expected, $result);
    }

    public function testGetNameWhenTheNameIsNull()
    {
        $entity = new \stdClass();
        $format = 'full';
        $locale = 'en_US';

        $this->provider1->expects($this->never())
            ->method('getName');

        $this->provider2->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn(null);

        $result = $this->entityNameResolver->getName($entity, $format, $locale);
        $this->assertNull($result);
    }

    public function testGetNameByFallbackFormat()
    {
        $entity = new \stdClass();
        $format = 'full';
        $locale = 'en_US';
        $expected = 'EntityName';

        $this->provider1->expects($this->once())
            ->method('getName')
            ->with($format, $locale, $this->identicalTo($entity))
            ->willReturn(false);

        $this->provider2->expects($this->exactly(2))
            ->method('getName')
            ->withConsecutive(
                [$format, $locale, $this->identicalTo($entity)],
                ['short', $locale, $this->identicalTo($entity)]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                $expected
            );

        $result = $this->entityNameResolver->getName($entity, $format, $locale);
        $this->assertEquals($expected, $result);
    }

    public function testGetNameWithoutChildProviders()
    {
        $entityNameResolver = $this->getEntityNameResolver([], 'full', ['full' => ['fallback' => null]]);
        $this->assertNull($entityNameResolver->getName(new \stdClass(), 'full', 'en_US'));
    }

    public function testGetNameDQLForUndefinedFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The unknown representation format "other".');

        $this->entityNameResolver->getNameDQL('Test\Entity', 'alias', 'other');
    }

    public function testGetNameDQLWhenRequestedFormatImplementedByRegisteredProviders()
    {
        $className = 'Test\Entity';
        $alias = 'entity_alias';
        $format = 'full';
        $locale = 'en_US';
        $expected = $alias . '.field';

        $this->provider1->expects($this->once())
            ->method('getNameDQL')
            ->with($format, $locale, $className, $alias)
            ->willReturn($expected);

        $this->provider2->expects($this->once())
            ->method('getNameDQL')
            ->with($format, $locale, $className, $alias)
            ->willReturn(false);

        $result = $this->entityNameResolver->getNameDQL($className, $alias, $format, $locale);
        $this->assertEquals($expected, $result);
    }

    public function testGetNameDQLByFallbackFormat()
    {
        $className = 'Test\Entity';
        $alias = 'entity_alias';
        $format = 'full';
        $locale = 'en_US';
        $expected = $alias . '.field';

        $this->provider1->expects($this->once())
            ->method('getNameDQL')
            ->with($format, $locale, $className, $alias)
            ->willReturn(false);

        $this->provider2->expects($this->exactly(2))
            ->method('getNameDQL')
            ->withConsecutive(
                [$format, $locale, $className, $alias],
                ['short', $locale, $className, $alias]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                $expected
            );

        $result = $this->entityNameResolver->getNameDQL($className, $alias, $format, $locale);
        $this->assertEquals($expected, $result);
    }

    public function testGetNameDQLWithoutChildProviders()
    {
        $entityNameResolver = $this->getEntityNameResolver([], 'full', ['full' => ['fallback' => null]]);
        $this->assertNull($entityNameResolver->getNameDQL('Test\Entity', 'entity_alias', 'full', 'en_US'));
    }

    public function testPrepareNameDQLForEmptyExpr()
    {
        self::assertEquals(
            '\'\'',
            $this->entityNameResolver->prepareNameDQL(null)
        );
    }

    public function testPrepareNameDQLWithoutCastToString()
    {
        self::assertEquals(
            'e.name',
            $this->entityNameResolver->prepareNameDQL('e.name')
        );
    }

    public function testPrepareNameDQLWithCastToString()
    {
        self::assertEquals(
            'CAST(e.name AS string)',
            $this->entityNameResolver->prepareNameDQL('e.name', true)
        );
    }
}
