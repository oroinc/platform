<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Entity;

use Oro\Bundle\CommentBundle\Entity\Comments;

class CommentsTest extends AbstractEntityTestCase
{
    /** @var Comments */
    protected $entity;

    /**
     * {@inheritDoc}
     */
    public function getEntityFQCN()
    {
        return 'Oro\Bundle\CommentBundle\Entity\Comments';
    }

    public function testPrePersist()
    {
        $this->entity->prePersist();

        $this->assertNotNull($this->entity->getCreatedAt());
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
        $owner        = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
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
