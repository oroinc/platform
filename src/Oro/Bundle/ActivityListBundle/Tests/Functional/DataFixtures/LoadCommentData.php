<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadCommentData extends AbstractFixture implements DependentFixtureInterface
{
    private array $commentData = [
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
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadEmailData::class, LoadOrganization::class, LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->commentData as $data) {
            $entity = new Comment();
            $entity->setTarget($this->getReference($data['target']));
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setOwner($this->getReference(LoadUser::USER));
            $entity->setMessage($data['message']);
            $entity->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
