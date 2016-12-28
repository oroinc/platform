<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Entity;

use Oro\Bundle\CommentBundle\Entity\Comment;

class CommentTest extends AbstractEntityTestCase
{
    /** @var Comment */
    protected $entity;

    /**
     * {@inheritDoc}
     */
    public function getEntityFQCN()
    {
        return 'Oro\Bundle\CommentBundle\Entity\Comment';
    }

    public function testPrePersist()
    {
        $this->entity->prePersist();

        $this->assertNotNull($this->entity->getCreatedAt());
        $this->assertNotNull($this->entity->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $this->entity->preUpdate();

        $this->assertNotNull($this->entity->getUpdatedAt());
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $owner        = $this->createMock('Oro\Bundle\UserBundle\Entity\User');
        $organization = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $createdAt    = new \DateTime('now');
        $updatedAt    = new \DateTime('now');

        return [
            'message'      => ['message', 'some test message', 'some test message'],
            'updatedBy'    => ['updatedBy', $owner, $owner],
            'owner'        => ['owner', $owner, $owner],
            'organization' => ['organization', $organization, $organization],
            'createdAt'    => ['createdAt', $createdAt, $createdAt],
            'updatedAt'    => ['updatedAt', $updatedAt, $updatedAt],
        ];
    }
}
