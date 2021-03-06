<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Helper;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method ContainerInterface getContainer()
 */
trait AdminUserTrait
{
    protected function getAdminUser(): User
    {
        return static::getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroUserBundle:User')
            ->findOneBy([
                'email' => WebTestCase::AUTH_USER
            ]);
    }
}
