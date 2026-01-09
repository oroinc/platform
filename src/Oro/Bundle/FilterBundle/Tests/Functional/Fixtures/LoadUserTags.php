<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserTags extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private static array $tags = [
        'u1@example.com' => ['Friends'],
        'u2@example.com' => ['Developer', 'Wholesale'],
        'u3@example.com' => [],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUserWithBUAndOrganization::class, LoadOrganization::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $tokenStorage = $this->container->get('security.token_storage');
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $tokenStorage->setToken(new UsernamePasswordOrganizationToken(
            $user,
            'main',
            $this->getReference(LoadOrganization::ORGANIZATION),
            $user->getUserRoles()
        ));
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
        $tokenStorage->setToken(null);
    }

    private function buildTagEntities(ObjectManager $manager): array
    {
        $tagEntities = [];
        $tags = array_unique(array_merge(...array_values(self::$tags)));
        foreach ($tags as $tag) {
            $tagEntity = new Tag($tag);
            $tagEntity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $tagEntities[$tag] = $tagEntity;
            $manager->persist($tagEntity);
            $this->setReference('tag.' . $tag, $tagEntity);
        }

        return $tagEntities;
    }
}
