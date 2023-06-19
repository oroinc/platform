<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Environment;

class TestEntityNameResolverConfigurableClassesProvider implements TestEntityNameResolverClassesProviderInterface
{
    private const REASON = 'developer';

    private TestEntityNameResolverClassesProviderInterface $innerProvider;
    /** @var string[] */
    private array $entityClasses;

    public function __construct(TestEntityNameResolverClassesProviderInterface $innerProvider, array $entityClasses)
    {
        $this->innerProvider = $innerProvider;
        $this->entityClasses = $entityClasses;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClasses(): array
    {
        $entityClasses = $this->innerProvider->getEntityClasses();
        foreach ($this->entityClasses as $entityClass) {
            if (!isset($entityClasses[$entityClass])) {
                $entityClasses[$entityClass] = [self::REASON];
            } elseif (!\in_array(self::REASON, $entityClasses[$entityClass], true)) {
                $entityClasses[$entityClass][] = self::REASON;
            }
        }

        return $entityClasses;
    }
}
