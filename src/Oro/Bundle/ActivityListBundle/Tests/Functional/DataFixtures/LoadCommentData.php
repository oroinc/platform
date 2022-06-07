<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadCommentData extends AbstractFixture implements DependentFixtureInterface
{
    private $commentData = [
        [
            'target' => 'first_activity',
            'message' => 'First comment'
        ],
        [
            'target' => 'second_activity',
            'message' => 'Second comment'
        ],
        [
            'target' => 'second_activity',
            'message' => 'Third comment'
        ],
        [
            'target' => 'third_activity',
            'message' => 'Fourth comment'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadEmailData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $adminUser = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $organization = $manager->getRepository(Organization::class)->getFirst();

        foreach ($this->commentData as $data) {
            $entity = new Comment();
            $entity->setTarget($this->getReference($data['target']));
            $entity->setOrganization($organization);
            $entity->setOwner($adminUser);
            $entity->setMessage($data['message']);
            $entity->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
