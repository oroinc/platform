<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Environment;

use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverClassesProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

class TestEntityNameResolverClassesProvider implements TestEntityNameResolverClassesProviderInterface
{
    private const REASON = 'search';

    private TestEntityNameResolverClassesProviderInterface $innerProvider;
    private SearchMappingProvider $searchMappingProvider;

    public function __construct(
        TestEntityNameResolverClassesProviderInterface $innerProvider,
        SearchMappingProvider $searchMappingProvider
    ) {
        $this->innerProvider = $innerProvider;
        $this->searchMappingProvider = $searchMappingProvider;
    }

    #[\Override]
    public function getEntityClasses(): array
    {
        $entityClasses = $this->innerProvider->getEntityClasses();
        $searchableEntityClasses = $this->searchMappingProvider->getEntityClasses();
        foreach ($searchableEntityClasses as $entityClass) {
            if (str_starts_with($entityClass, ExtendClassLoadingUtils::getEntityNamespace())) {
                continue;
            }
            if (is_a($entityClass, TestFrameworkEntityInterface::class, true)) {
                continue;
            }
            if (!isset($entityClasses[$entityClass])) {
                $entityClasses[$entityClass] = [self::REASON];
            } elseif (!\in_array(self::REASON, $entityClasses[$entityClass], true)) {
                $entityClasses[$entityClass][] = self::REASON;
            }
        }

        return $entityClasses;
    }
}
