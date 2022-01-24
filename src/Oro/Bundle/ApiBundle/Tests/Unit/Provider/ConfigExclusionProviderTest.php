<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class ConfigExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigExclusionProvider */
    private $provider;

    protected function setUp(): void
    {
        $hierarchyProvider = $this->createMock(EntityHierarchyProviderInterface::class);
        $hierarchyProvider->expects(self::any())
            ->method('getHierarchyForClassName')
            ->willReturnMap([
                ['Test\Entity\Entity1', ['Test\Entity\BaseEntity1']],
                ['Test\Entity\Entity2', []],
                ['Test\Entity\Entity3', []],
                ['Test\Entity\Entity4', []],
                ['Test\Entity\Entity5', []]
            ]);

        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::any())
            ->method('getExclusions')
            ->willReturn([
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field3'],
                ['entity' => 'Test\Entity\Entity2'],
                ['entity' => 'Test\Entity\Entity3']
            ]);
        $configCache->expects(self::any())
            ->method('getInclusions')
            ->willReturn([
                ['entity' => 'Test\Entity\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity\BaseEntity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity\Entity3']
            ]);

        $childProvider = $this->createMock(ExclusionProviderInterface::class);
        $childProvider->expects(self::any())
            ->method('isIgnoredEntity')
            ->willReturnCallback(function (string $className): bool {
                if ($className === 'Test\Entity\Entity4') {
                    return true;
                }
                if ($className === 'Test\Entity\Entity1' || $className === 'Test\Entity\Entity5') {
                    return false;
                }
                self::fail(sprintf('Unexpected "isIgnoredEntity" call for "%s" entity', $className));
            });
        $childProvider->expects(self::any())
            ->method('isIgnoredField')
            ->willReturnCallback(function (ClassMetadata $metadata, string $fieldName): bool {
                self::assertEquals(
                    'Test\Entity\Entity1',
                    $metadata->getName(),
                    sprintf('Unexpected "isIgnoredRelation" call for "%s" entity', $metadata->getName())
                );
                if ($fieldName === 'field4') {
                    return true;
                }
                if ($fieldName === 'field5') {
                    return false;
                }
                self::fail(sprintf('Unexpected "isIgnoredField" call for "%s" field', $fieldName));
            });
        $childProvider->expects(self::any())
            ->method('isIgnoredRelation')
            ->willReturnCallback(function (ClassMetadata $metadata, string $fieldName): bool {
                self::assertEquals(
                    'Test\Entity\Entity1',
                    $metadata->getName(),
                    sprintf('Unexpected "isIgnoredRelation" call for "%s" entity', $metadata->getName())
                );
                if ($fieldName === 'field4') {
                    return true;
                }
                if ($fieldName === 'field5') {
                    return false;
                }
                self::fail(sprintf('Unexpected "isIgnoredRelation" call for "%s" field', $fieldName));
            });

        $this->provider = new ConfigExclusionProvider($hierarchyProvider, $configCache);
        $this->provider->addProvider($childProvider);
    }

    /**
     * @dataProvider entityProvider
     */
    public function testIsIgnoredEntity(string $className, bool $expected)
    {
        self::assertSame(
            $expected,
            $this->provider->isIgnoredEntity($className)
        );
    }

    public function entityProvider(): array
    {
        return [
            ['Test\Entity\Entity1', false],
            ['Test\Entity\Entity2', true],
            ['Test\Entity\Entity3', false],
            ['Test\Entity\Entity4', true],
            ['Test\Entity\Entity5', false]
        ];
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredField(ClassMetadata $metadata, string $fieldName, bool $expected)
    {
        self::assertSame(
            $expected,
            $this->provider->isIgnoredField($metadata, $fieldName)
        );
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredRelation(ClassMetadata $metadata, string $associationName, bool $expected)
    {
        self::assertSame(
            $expected,
            $this->provider->isIgnoredRelation($metadata, $associationName)
        );
    }

    public function fieldProvider(): array
    {
        $entity1 = new ClassMetadata('Test\Entity\Entity1');
        $entity2 = new ClassMetadata('Test\Entity\Entity2');
        $entity3 = new ClassMetadata('Test\Entity\Entity3');

        return [
            [$entity1, 'field1', false],
            [$entity1, 'field2', false],
            [$entity1, 'field3', true],
            [$entity1, 'field4', true],
            [$entity1, 'field5', false],
            [$entity2, 'field1', true],
            [$entity3, 'field1', false]
        ];
    }
}
