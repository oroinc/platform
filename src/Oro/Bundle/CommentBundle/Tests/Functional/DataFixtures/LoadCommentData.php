<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures;

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
            'message' => 'First comment',
            'createdAt' => 'now -5 days',
            'updatedAt' => 'now -5 days',
        ],
        [
            'message' => 'Second comment',
            'createdAt' => 'now -4 days',
            'updatedAt' => 'now -4 days',
        ],
        [
            'message' => 'Third comment',
            'createdAt' => 'now +3 days',
            'updatedAt' => 'now +5 days',
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadEmailData::class, LoadOrganization::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->commentData as $data) {
            $entity = new Comment();
            $entity->setTarget($this->getReference('default_activity'));
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setOwner($this->getReference(LoadUser::USER));
            $entity->setMessage($data['message']);
            $entity->setCreatedAt(new \DateTime($data['createdAt'], new \DateTimeZone('UTC')));
            $entity->setUpdatedAt(new \DateTime($data['updatedAt'], new \DateTimeZone('UTC')));
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
