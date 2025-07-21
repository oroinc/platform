<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityNameResolverTest extends TestCase
{
    private EntityNameProviderInterface&MockObject $provider1;
    private EntityNameProviderInterface&MockObject $provider2;
    private EntityNameResolver $entityNameResolver;

    #[\Override]
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

    public function testGetNameForUndefinedFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The unknown representation format "other".');

        $this->entityNameResolver->getName(new \stdClass(), 'other');
    }

    public function testGetNameForNullEntity(): void
    {
        $this->provider1->expects($this->never())
            ->method('getName');
        $this->provider2->expects($this->never())
            ->method('getName');

        $this->assertNull($this->entityNameResolver->getName(null, 'full'));
    }

    public function testGetNameWhenRequestedFormatImplementedByRegisteredProviders(): void
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

    public function testGetNameWhenTheNameIsNull(): void
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

    public function testGetNameByFallbackFormat(): void
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

    public function testGetNameWithoutChildProviders(): void
    {
        $entityNameResolver = $this->getEntityNameResolver([], 'full', ['full' => ['fallback' => null]]);
        $this->assertNull($entityNameResolver->getName(new \stdClass(), 'full', 'en_US'));
    }

    public function testGetNameDQLForUndefinedFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The unknown representation format "other".');

        $this->entityNameResolver->getNameDQL('Test\Entity', 'alias', 'other');
    }

    public function testGetNameDQLWhenRequestedFormatImplementedByRegisteredProviders(): void
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

    public function testGetNameDQLByFallbackFormat(): void
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

    public function testGetNameDQLWithoutChildProviders(): void
    {
        $entityNameResolver = $this->getEntityNameResolver([], 'full', ['full' => ['fallback' => null]]);
        $this->assertNull($entityNameResolver->getNameDQL('Test\Entity', 'entity_alias', 'full', 'en_US'));
    }

    public function testPrepareNameDQLForEmptyExpr(): void
    {
        self::assertEquals(
            '\'\'',
            $this->entityNameResolver->prepareNameDQL(null)
        );
    }

    public function testPrepareNameDQLWithoutCastToString(): void
    {
        self::assertEquals(
            'e.name',
            $this->entityNameResolver->prepareNameDQL('e.name')
        );
    }

    public function testPrepareNameDQLWithCastToString(): void
    {
        self::assertEquals(
            'CAST(e.name AS string)',
            $this->entityNameResolver->prepareNameDQL('e.name', true)
        );
    }
}
