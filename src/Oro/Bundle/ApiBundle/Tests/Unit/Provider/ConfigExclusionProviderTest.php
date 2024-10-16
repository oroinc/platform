<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class ConfigExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    private ConfigExclusionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $hierarchyProvider = $this->createMock(EntityHierarchyProviderInterface::class);
        $hierarchyProvider->expects(self::any())
            ->method('getHierarchyForClassName')
            ->willReturnCallback(function (string $className): array {
                return 'Test\Entity1' === $className
                    ? ['Test\BaseEntity1']
                    : [];
            });

        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::any())
            ->method('getExclusions')
            ->willReturn([
                ['entity' => 'Test\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\Entity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity1', 'field' => 'field3'],
                ['entity' => 'Test\Entity2'],
                ['entity' => 'Test\Entity3']
            ]);
        $configCache->expects(self::any())
            ->method('getInclusions')
            ->willReturn([
                ['entity' => 'Test\Entity1', 'field' => 'field1'],
                ['entity' => 'Test\BaseEntity1', 'field' => 'field2'],
                ['entity' => 'Test\Entity3'],
                ['entity' => 'Test\Entity6'],
                ['entity' => 'Test\Entity7', 'field' => 'field1']
            ]);

        $this->provider = new ConfigExclusionProvider(
            $hierarchyProvider,
            $configCache,
            $this->getExclusionProvider(
                $this->getEntityCheckerForSystemProvider(),
                $this->getFieldCheckerForSystemProvider()
            )
        );
        $this->provider->addProvider(
            $this->getExclusionProvider(
                $this->getEntityCheckerForChildProvider(),
                $this->getFieldCheckerForChildProvider()
            )
        );
    }

    private function getEntityCheckerForSystemProvider(): callable
    {
        return function (string $className): bool {
            if ($className === 'Test\Entity4') {
                return true;
            }
            if ($className === 'Test\Entity1' || $className === 'Test\Entity5') {
                return false;
            }
            self::fail(sprintf('SYSTEM: Unexpected "isIgnoredEntity" call for "%s" entity', $className));
        };
    }

    private function getFieldCheckerForSystemProvider(): callable
    {
        return function (string $method, ClassMetadata $metadata, string $fieldName): bool {
            if ($metadata->getName() === 'Test\Entity1') {
                if ($fieldName === 'field4' || $fieldName === 'field5') {
                    return false;
                }
            } elseif ($metadata->getName() === 'Test\Entity7') {
                if ($fieldName === 'field2') {
                    return false;
                }
                if ($fieldName === 'field3') {
                    return true;
                }
                if ($fieldName === 'field4') {
                    return false;
                }
            }
            self::fail(sprintf(
                'SYSTEM: Unexpected "%s" call for "%s::%s" field',
                $method,
                $metadata->getName(),
                $fieldName
            ));
        };
    }

    private function getEntityCheckerForChildProvider(): callable
    {
        return function (string $className): bool {
            if ($className === 'Test\Entity4') {
                return true;
            }
            if (in_array($className, ['Test\Entity1', 'Test\Entity5', 'Test\Entity7'], true)) {
                return false;
            }
            self::fail(sprintf('Unexpected "isIgnoredEntity" call for "%s" entity', $className));
        };
    }

    private function getFieldCheckerForChildProvider(): callable
    {
        return function (string $method, ClassMetadata $metadata, string $fieldName): bool {
            if ($metadata->getName() === 'Test\Entity1') {
                if ($fieldName === 'field4') {
                    return true;
                }
                if ($fieldName === 'field5') {
                    return false;
                }
            } elseif ($metadata->getName() === 'Test\Entity6') {
                if ($fieldName === 'field1') {
                    return false;
                }
            } elseif ($metadata->getName() === 'Test\Entity7') {
                if ($fieldName === 'field2') {
                    return false;
                }
                if ($fieldName === 'field4') {
                    return true;
                }
            }
            self::fail(sprintf(
                'Unexpected "%s" call for "%s::%s" field',
                $method,
                $metadata->getName(),
                $fieldName
            ));
        };
    }

    private function getExclusionProvider(callable $entityChecker, callable $fieldChecker): ExclusionProviderInterface
    {
        $provider = $this->createMock(ExclusionProviderInterface::class);
        $provider->expects(self::any())
            ->method('isIgnoredEntity')
            ->willReturnCallback($entityChecker);
        $provider->expects(self::any())
            ->method('isIgnoredField')
            ->willReturnCallback(function (ClassMetadata $metadata, string $fieldName) use ($fieldChecker) {
                return $fieldChecker('isIgnoredField', $metadata, $fieldName);
            });
        $provider->expects(self::any())
            ->method('isIgnoredRelation')
            ->willReturnCallback(function (ClassMetadata $metadata, string $fieldName) use ($fieldChecker) {
                return $fieldChecker('isIgnoredRelation', $metadata, $fieldName);
            });

        return $provider;
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
            ['Test\Entity1', false],
            ['Test\Entity2', true],
            ['Test\Entity3', false],
            ['Test\Entity4', true],
            ['Test\Entity5', false],
            ['Test\Entity6', false],
            ['Test\Entity7', false]
        ];
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredField(string $className, string $fieldName, bool $expected)
    {
        self::assertSame(
            $expected,
            $this->provider->isIgnoredField(new ClassMetadata($className), $fieldName)
        );
    }

    /**
     * @dataProvider fieldProvider
     */
    public function testIsIgnoredRelation(string $className, string $associationName, bool $expected)
    {
        self::assertSame(
            $expected,
            $this->provider->isIgnoredRelation(new ClassMetadata($className), $associationName)
        );
    }

    public function fieldProvider(): array
    {
        return [
            ['Test\Entity1', 'field1', false],
            ['Test\Entity1', 'field2', false],
            ['Test\Entity1', 'field3', true],
            ['Test\Entity1', 'field4', true],
            ['Test\Entity1', 'field5', false],
            ['Test\Entity2', 'field1', true],
            ['Test\Entity3', 'field1', true],
            ['Test\Entity6', 'field1', false],
            ['Test\Entity7', 'field1', false],
            ['Test\Entity7', 'field2', false],
            ['Test\Entity7', 'field3', true],
            ['Test\Entity7', 'field4', true]
        ];
    }
}
