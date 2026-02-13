<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub\PersistentValidatorCacheWarmerTestEntity;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub\TestMappedClassesLoader;
use Oro\Bundle\EntityExtendBundle\Validator\PersistentValidatorCacheWarmer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\When;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validation;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PersistentValidatorCacheWarmerTest extends TestCase
{
    private ArrayAdapter $persistentCache;
    private ExpressionLanguage&MockObject $expressionLanguage;

    #[\Override]
    protected function setUp(): void
    {
        $this->persistentCache = new ArrayAdapter();
        $this->expressionLanguage = $this->createMock(ExpressionLanguage::class);
    }

    public function testIsOptional(): void
    {
        $warmer = $this->createWarmer($this->createLoader([]));

        self::assertTrue($warmer->isOptional());
    }

    public function testWarmUpSavesMetadataToPersistentCache(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addPropertyConstraint('name', new NotBlank());
            },
        ]);

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());

        $cacheKey = str_replace('\\', '.', $entityClass);
        self::assertTrue($this->persistentCache->hasItem($cacheKey));
    }

    public function testWarmUpParsesExpressionConstraint(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addConstraint(new Expression(
                    expression: 'value !== null',
                    negate: false,
                ));
            },
        ]);

        $this->expressionLanguage->expects(self::once())
            ->method('parse')
            ->with('value !== null', self::callback(function (array $names): bool {
                sort($names);
                return $names === ['context', 'this', 'value'];
            }))
            ->willReturn($this->createMock(ParsedExpression::class));

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpParsesPropertyExpressionConstraint(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addPropertyConstraint('name', new Expression(
                    expression: 'value !== null || this.isOptional()',
                    negate: false,
                ));
            },
        ]);

        $this->expressionLanguage->expects(self::once())
            ->method('parse')
            ->with('value !== null || this.isOptional()', self::callback(function (array $names): bool {
                sort($names);
                return $names === ['context', 'this', 'value'];
            }))
            ->willReturn($this->createMock(ParsedExpression::class));

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpParsesWhenConstraint(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addPropertyConstraint('name', new When(
                    expression: 'this.getType() == "foo"',
                    constraints: [new NotBlank()],
                ));
            },
        ]);

        $this->expressionLanguage->expects(self::once())
            ->method('parse')
            ->with('this.getType() == "foo"', self::callback(function (array $names): bool {
                sort($names);
                return $names === ['context', 'this', 'value'];
            }))
            ->willReturn($this->createMock(ParsedExpression::class));

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpParsesExpressionWithCustomValues(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addConstraint(new Expression(
                    expression: 'value > limit',
                    values: ['limit' => 10],
                    negate: false,
                ));
            },
        ]);

        $this->expressionLanguage->expects(self::once())
            ->method('parse')
            ->with('value > limit', self::callback(function (array $names): bool {
                sort($names);
                return $names === ['context', 'limit', 'this', 'value'];
            }))
            ->willReturn($this->createMock(ParsedExpression::class));

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpParsesExpressionWithDuplicateVariableNames(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addConstraint(new Expression(
                    expression: 'value > limit',
                    // Custom 'value' key - will result in duplicate 'value' in the merged array
                    values: ['value' => 100, 'limit' => 10],
                    negate: false,
                ));
            },
        ]);

        $this->expressionLanguage->expects(self::once())
            ->method('parse')
            ->with('value > limit', self::callback(function (array $names): bool {
                sort($names);
                // Duplicates are preserved in array_merge result, but doesn't matter
                // because ExpressionLanguage::parse() will asort() them anyway
                return \in_array('value', $names) && \in_array('limit', $names)
                    && \in_array('this', $names) && \in_array('context', $names);
            }))
            ->willReturn($this->createMock(ParsedExpression::class));

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpParsesMultipleExpressions(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addConstraint(new Expression(
                    expression: 'value !== null',
                    negate: false,
                ));
                $metadata->addPropertyConstraint('name', new Expression(
                    expression: 'value.length > 0',
                    negate: false,
                ));
            },
        ]);

        $parsedExpressions = [];
        $this->expressionLanguage->expects(self::exactly(2))
            ->method('parse')
            ->willReturnCallback(function (string $expression, array $names) use (&$parsedExpressions) {
                $parsedExpressions[] = $expression;
                return $this->createMock(ParsedExpression::class);
            });

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());

        self::assertContains('value !== null', $parsedExpressions);
        self::assertContains('value.length > 0', $parsedExpressions);
    }

    public function testWarmUpDoesNotParseWhenNoExpressionConstraints(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addPropertyConstraint('name', new NotBlank());
            },
        ]);

        $this->expressionLanguage->expects(self::never())
            ->method('parse');

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpThrowsOnExpressionParseError(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addConstraint(new Expression(
                    expression: 'invalid %%',
                    negate: false,
                ));
            },
        ]);

        $this->expressionLanguage->expects(self::once())
            ->method('parse')
            ->willThrowException(new \RuntimeException('Syntax error'));

        $warmer = $this->createWarmer($loader);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Syntax error');

        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpSkipsClosureWhenConstraint(): void
    {
        $entityClass = PersistentValidatorCacheWarmerTestEntity::class;
        $loader = $this->createLoader([
            $entityClass => function (ClassMetadata $metadata) {
                $metadata->addPropertyConstraint('name', new When(
                    expression: static fn () => true,
                    constraints: [new NotBlank()],
                ));
            },
        ]);

        $this->expressionLanguage->expects(self::never())
            ->method('parse');

        $warmer = $this->createWarmer($loader);
        $warmer->warmUp(sys_get_temp_dir());
    }

    public function testWarmUpWithEmptyLoaders(): void
    {
        $loader = $this->createLoader([]);

        $this->expressionLanguage->expects(self::never())
            ->method('parse');

        $warmer = $this->createWarmer($loader);
        $result = $warmer->warmUp(sys_get_temp_dir());

        self::assertIsArray($result);
    }

    private function createWarmer(TestMappedClassesLoader $loader): PersistentValidatorCacheWarmer
    {
        $validatorBuilder = Validation::createValidatorBuilder();
        $validatorBuilder->addLoader($loader);

        return new PersistentValidatorCacheWarmer(
            $this->persistentCache,
            $validatorBuilder,
            sys_get_temp_dir() . '/test_validation.php',
            $this->expressionLanguage
        );
    }

    /**
     * @param array<string, callable> $classCallbacks Map of class name to callback that configures ClassMetadata
     */
    private function createLoader(array $classCallbacks): TestMappedClassesLoader&MockObject
    {
        $loader = $this->createMock(TestMappedClassesLoader::class);
        $loader->expects(self::any())
            ->method('getMappedClasses')
            ->willReturn(array_keys($classCallbacks));
        $loader->expects(self::any())
            ->method('loadClassMetadata')
            ->willReturnCallback(function (ClassMetadata $metadata) use ($classCallbacks) {
                $class = $metadata->getClassName();
                if (isset($classCallbacks[$class])) {
                    $classCallbacks[$class]($metadata);
                    return true;
                }
                return false;
            });

        return $loader;
    }
}
