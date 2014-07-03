<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return 'testUser';
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
}
