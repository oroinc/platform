<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserLoginAttempt;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class UserLoginAttemptTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            'username'  => ['username', 'steve', false],
            'source'    => ['source', 12, false],
            'user'      => ['user', new User(), false],
            'ip'        => ['ip', '127.0.0.1', false],
            'userAgent' => ['userAgent', 'Chrome', false],
            'context'   => ['context', ['some' => 'data'], false],
            'attemptAt' => ['attemptAt', new \DateTime(), false]
        ];

        self::assertPropertyAccessors(new UserLoginAttempt(), $properties);
    }

    public function testGetId(): void
    {
        $id = UUIDGenerator::v4();
        $entity = new UserLoginAttempt();
        $entity->setId($id);
        self::assertSame($id, $entity->getId());
    }
}
