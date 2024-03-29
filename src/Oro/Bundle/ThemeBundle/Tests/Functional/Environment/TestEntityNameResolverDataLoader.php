<?php

namespace Oro\Bundle\ThemeBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (ThemeConfiguration::class === $entityClass) {
            $themeConfiguration = new ThemeConfiguration();
            $themeConfiguration->setOrganization($repository->getReference('organization'));
            $themeConfiguration->setOwner($repository->getReference('business_unit'));
            $themeConfiguration->setName('Test Theme Configuration');
            $themeConfiguration->setTheme('default');
            $repository->setReference('themeConfiguration', $themeConfiguration);
            $em->persist($themeConfiguration);
            $em->flush();

            return ['themeConfiguration'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (ThemeConfiguration::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? 'Test Theme Configuration'
                : 'Storefront Test Theme Configuration default';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}
