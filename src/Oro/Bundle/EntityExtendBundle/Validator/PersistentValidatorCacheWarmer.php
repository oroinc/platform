<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\ValidatorCacheWarmer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Composite;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\When;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use Symfony\Component\VarExporter\VarExporter;

/**
 * Warms up entity validator mapping metadata with persistent cache usage instead of php array file.
 * Also pre-parses expression language expressions used in Expression and When constraints
 * to prevent runtime cache writes to the filesystem.
 */
class PersistentValidatorCacheWarmer extends ValidatorCacheWarmer
{
    private const string DUMMY_DIR_TO_FAIL_IF_USED = '__VALIDATOR_CACHE_MUST_WRITE_TO_PERSISTENT_CACHE_ERROR__';
    private const EXPRESSION_VARIABLES = ['value', 'this', 'context'];

    public function __construct(
        private readonly CacheItemPoolInterface $persistentCache,
        ValidatorBuilder $validatorBuilder,
        string $phpArrayFile,
        private readonly ExpressionLanguage $expressionLanguage
    ) {
        // override cache to persistent instead of $phpArrayFile
        parent::__construct($validatorBuilder, $phpArrayFile);
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $arrayAdapter = new ArrayAdapter();
        spl_autoload_register([ClassExistenceResource::class, 'throwOnRequiredClass']);
        try {
            if (!$this->doWarmUp($cacheDir, $arrayAdapter, $buildDir ?: self::DUMMY_DIR_TO_FAIL_IF_USED)) {
                return [];
            }
        } finally {
            spl_autoload_unregister([ClassExistenceResource::class, 'throwOnRequiredClass']);
        }
        $values = array_map(
            fn ($val) => null !== $val ? unserialize($val) : null,
            $arrayAdapter->getValues()
        );
        /** customization start */
        $this->persistentCache->clear();
        $preload = [];
        $isStaticValue = true;
        foreach ($values as $key => $value) {
            VarExporter::export($value, $isStaticValue, $preload);

            $cacheItem = $this->persistentCache->getItem($key);
            $cacheItem->set($value);
            $this->persistentCache->save($cacheItem);

            if ($value instanceof ClassMetadataInterface) {
                $this->warmUpExpressions($value);
            }
        }

        return $preload;
        /** customization end */
    }

    private function warmUpExpressions(ClassMetadataInterface $metadata): void
    {
        foreach ($metadata->getConstraints() as $constraint) {
            $this->warmUpConstraintExpression($constraint);
        }

        foreach ($metadata->getConstrainedProperties() as $property) {
            foreach ($metadata->getPropertyMetadata($property) as $propertyMetadata) {
                foreach ($propertyMetadata->getConstraints() as $constraint) {
                    $this->warmUpConstraintExpression($constraint);
                }
            }
        }
    }

    private function warmUpConstraintExpression(Constraint $constraint): void
    {
        if ($constraint instanceof Expression) {
            $this->parseExpression(
                (string) $constraint->expression,
                array_merge(self::EXPRESSION_VARIABLES, array_keys($constraint->values))
            );
        } elseif ($constraint instanceof When && \is_string($constraint->expression)) {
            $this->parseExpression(
                $constraint->expression,
                array_merge(self::EXPRESSION_VARIABLES, array_keys($constraint->values))
            );
        }

        if ($constraint instanceof Composite) {
            foreach ($constraint->getNestedConstraints() as $nestedConstraint) {
                $this->warmUpConstraintExpression($nestedConstraint);
            }
        }
    }

    private function parseExpression(string $expression, array $names): void
    {
        $this->expressionLanguage->parse($expression, $names);
    }
}
