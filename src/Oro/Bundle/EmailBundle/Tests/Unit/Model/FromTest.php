<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\From;

class FromTest extends \PHPUnit\Framework\TestCase
{
    private const EMAIL_ADDRESS = 'some@mail.com';
    private const NAME = 'Some Name';
    private const OTHER_EMAIL_ADDRESS = 'other@mail.com';
    private const OTHER_NAME = 'Other Name';

    public function testPopulateWithSingleEmailAddress(): void
    {
        $message = $this->createMock(\Swift_Message::class);
        $message->expects($this->once())
            ->method('setFrom')
            ->with(self::EMAIL_ADDRESS);

        $from = From::emailAddress(self::EMAIL_ADDRESS);

        $from->populate($message);
    }

    public function testPopulateWithSingleEmailAddressAndName(): void
    {
        $message = $this->createMock(\Swift_Message::class);
        $message->expects($this->once())
            ->method('setFrom')
            ->with(self::EMAIL_ADDRESS, self::NAME);

        $from = From::emailAddress(self::EMAIL_ADDRESS, self::NAME);

        $from->populate($message);
    }

    public function testPopulateWithMultipleEmailAddresses(): void
    {
        $message = $this->createMock(\Swift_Message::class);
        $message->expects($this->once())
            ->method('setFrom')
            ->with([self::EMAIL_ADDRESS, self::OTHER_EMAIL_ADDRESS]);

        $from = From::emailAddresses([self::EMAIL_ADDRESS, self::OTHER_EMAIL_ADDRESS]);

        $from->populate($message);
    }

    public function testPopulateWithMultipleEmailAddressesAndNames(): void
    {
        $message = $this->createMock(\Swift_Message::class);
        $message->expects($this->once())
            ->method('setFrom')
            ->with([
                self::EMAIL_ADDRESS => self::NAME,
                self::OTHER_EMAIL_ADDRESS => self::OTHER_NAME
            ]);

        $from = From::emailAddresses([
            self::EMAIL_ADDRESS => self::NAME,
            self::OTHER_EMAIL_ADDRESS => self::OTHER_NAME
        ]);

        $from->populate($message);
    }
}
