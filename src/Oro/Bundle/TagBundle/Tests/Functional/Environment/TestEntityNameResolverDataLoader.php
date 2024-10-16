<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taxonomy;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Tag::class === $entityClass) {
            $tag = new Tag();
            $tag->setOrganization($repository->getReference('organization'));
            $tag->setOwner($repository->getReference('user'));
            $tag->setName('Test Tag');
            $repository->setReference('tag', $tag);
            $em->persist($tag);
            $em->flush();

            return ['tag'];
        }

        if (Taxonomy::class === $entityClass) {
            $taxonomy = new Taxonomy();
            $taxonomy->setOrganization($repository->getReference('organization'));
            $taxonomy->setOwner($repository->getReference('user'));
            $taxonomy->setName('Test Taxonomy');
            $taxonomy->setBackgroundColor('#FF0000');
            $repository->setReference('taxonomy', $taxonomy);
            $em->persist($taxonomy);
            $em->flush();

            return ['taxonomy'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Tag::class === $entityClass) {
            return 'Test Tag';
        }
        if (Taxonomy::class === $entityClass) {
            return 'Test Taxonomy';
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
