<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;

class EmailAddressWithContextTest extends \PHPUnit\Framework\TestCase
{
    const EMAIL = 'some@mail.com';

    public function testGetters(): void
    {
        $holder = new EmailHolderStub();
        $model = new EmailAddressWithContext(self::EMAIL, $holder);

        self::assertEquals(self::EMAIL, $model->getEmail());
        self::assertEquals($holder, $model->getContext());
    }

    public function testGettersNoContext(): void
    {
        $model = new EmailAddressWithContext(self::EMAIL);
        self::assertEquals(self::EMAIL, $model->getEmail());
        self::assertNull($model->getContext());
    }
}
