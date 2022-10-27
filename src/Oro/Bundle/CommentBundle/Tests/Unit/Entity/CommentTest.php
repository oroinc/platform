<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Entity;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CommentTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            'id'           => ['id', 1],
            'message'      => ['message', 'some test message'],
            'updatedBy'    => ['updatedBy', $this->createMock(User::class)],
            'owner'        => ['owner', $this->createMock(User::class)],
            'organization' => ['organization', $this->createMock(Organization::class)],
            'createdAt'    => ['createdAt', new \DateTime()],
            'updatedAt'    => ['updatedAt', new \DateTime()],
        ];

        $entity = new Comment();
        self::assertPropertyAccessors($entity, $properties);
    }

    public function testPrePersist()
    {
        $entity = new Comment();
        $entity->prePersist();

        self::assertNotNull($entity->getCreatedAt());
        self::assertNotNull($entity->getUpdatedAt());
        self::assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        self::assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());

        $existingCreatedAt = $entity->getCreatedAt();
        $existingUpdatedAt = $entity->getUpdatedAt();
        $entity->prePersist();
        self::assertSame($existingCreatedAt, $entity->getCreatedAt());
        self::assertNotSame($existingUpdatedAt, $entity->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $entity = new Comment();
        $entity->preUpdate();

        self::assertNotNull($entity->getUpdatedAt());

        $existingUpdatedAt = $entity->getUpdatedAt();
        $entity->preUpdate();
        self::assertNotSame($existingUpdatedAt, $entity->getUpdatedAt());
    }
}
