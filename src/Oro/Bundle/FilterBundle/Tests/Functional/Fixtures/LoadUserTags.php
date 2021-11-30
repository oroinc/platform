<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserTags extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var array */
    private static $tags = [
        'u1@example.com' => ['Friends'],
        'u2@example.com' => ['Developer', 'Wholesale'],
        'u3@example.com' => [],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserWithBUAndOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->initToken($manager);

        $tagEntities = $this->buildTagEntities($manager);
        $tagManager = $this->container->get('oro_tag.tag.manager');

        foreach (self::$tags as $reference => $tags) {
            /** @var User $user */
            $user = $this->getReference($reference);

            $tagManager->setTags(
                $user,
                new ArrayCollection(
                    array_map(
                        function ($tag) use ($tagEntities) {
                            return $tagEntities[$tag];
                        },
                        $tags
                    )
                )
            );
            $tagManager->saveTagging($user, false);
        }

        $manager->flush();
    }

    private function initToken(ObjectManager $manager): void
    {
        /** @var Organization $organization */
        $organization = $manager->getRepository(Organization::class)->getFirst();

        /** @var User $user */
        $user = $manager->getRepository(User::class)->findOneBy([], ['id' => 'ASC']);

        $tokenStorage = $this->container->get('security.token_storage');
        $tokenStorage->setToken(
            new UsernamePasswordOrganizationToken(
                $user,
                $user->getUsername(),
                'main',
                $organization,
                $user->getUserRoles()
            )
        );
    }

    private function buildTagEntities(ObjectManager $manager): array
    {
        /** @var Organization $organization */
        $organization = $manager->getRepository(Organization::class)->getFirst();

        $tags = array_unique(array_merge(...array_values(self::$tags)));

        $tagEntities = [];
        foreach ($tags as $tag) {
            $tagEntity = new Tag($tag);
            $tagEntity->setOrganization($organization);
            $tagEntities[$tag] = $tagEntity;

            $manager->persist($tagEntity);

            $this->setReference('tag.' . $tag, $tagEntity);
        }

        return $tagEntities;
    }
}
