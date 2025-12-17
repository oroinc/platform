<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Oro\Bundle\UserBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    private TokenAccessorInterface|MockObject $tokenAccessor;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private PlaceholderFilter $placeholderFilter;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->placeholderFilter = new PlaceholderFilter($this->tokenAccessor);
        $this->placeholderFilter->setEventDispatcher($this->eventDispatcher);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsApplicableOnUserPage(?object $user, bool $expected): void
    {
        if ($user instanceof User && $user->isEnabled()) {
            $this->eventDispatcher->expects(self::once())
                ->method('dispatch')
                ->with(
                    self::isInstanceOf(PasswordChangeEvent::class),
                    PasswordChangeEvent::BEFORE_PASSWORD_CHANGE
                )
                ->willReturnArgument(0);
        } else {
            $this->eventDispatcher->expects(self::never())
                ->method('dispatch');
        }

        self::assertEquals(
            $expected,
            $this->placeholderFilter->isPasswordManageEnabled($user)
        );
    }

    public function dataProvider(): array
    {
        $object = new \stdClass();
        $userDisabled = new User();
        $userDisabled->setEnabled(false);
        $userEnabled = new User();

        return [
            [null, false],
            [$object, false],
            [$userDisabled, false],
            [$userEnabled, true],
        ];
    }

    /**
     * @dataProvider isUserApplicableDataProvider
     */
    public function testIsUserApplicable(object $user, bool $expected): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        self::assertEquals($expected, $this->placeholderFilter->isUserApplicable());
    }

    public function isUserApplicableDataProvider(): array
    {
        return [
            [new \stdClass(), false],
            [new User(), true]
        ];
    }

    /**
     * @dataProvider isPasswordResetEnabledDataProvider
     */
    public function testisPasswordResetEnabled(int $tokenUserId, object $methodUser, bool $expected): void
    {
        $this->tokenAccessor->expects(self::any())
            ->method('getUserId')
            ->willReturn($tokenUserId);

        if ($methodUser instanceof User && $methodUser->isEnabled() && $tokenUserId !== $methodUser->getId()) {
            $this->eventDispatcher->expects(self::once())
                ->method('dispatch')
                ->with(
                    self::isInstanceOf(PasswordChangeEvent::class),
                    PasswordChangeEvent::BEFORE_PASSWORD_RESET
                )
                ->willReturnArgument(0);
        } else {
            $this->eventDispatcher->expects(self::never())
                ->method('dispatch');
        }

        self::assertEquals($expected, $this->placeholderFilter->isPasswordResetEnabled($methodUser));
    }

    public function isPasswordResetEnabledDataProvider(): array
    {
        $user1 = new User(1);
        $user1->setEnabled(true);
        $user2 = new User(2);
        $user2->setEnabled(true);
        $user2Disabled = new User(2);
        $user2Disabled->setEnabled(false);

        return [
            [1, new \stdClass(), false],
            [1, $user1, false],
            [1, $user2Disabled, false],
            [1, $user2, true]
        ];
    }

    public function testIsPasswordManageEnabledWhenEventDenies(): void
    {
        $user = new User();
        $user->setEnabled(true);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(PasswordChangeEvent::class),
                PasswordChangeEvent::BEFORE_PASSWORD_CHANGE
            )
            ->willReturnCallback(function (PasswordChangeEvent $event) {
                $event->disablePasswordChange('Test reason');
                return $event;
            });

        self::assertFalse($this->placeholderFilter->isPasswordManageEnabled($user));
    }

    public function testIsPasswordResetEnabledWhenEventDenies(): void
    {
        $user = new User(2);
        $user->setEnabled(true);

        $this->tokenAccessor->expects(self::once())
            ->method('getUserId')
            ->willReturn(1);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(PasswordChangeEvent::class),
                PasswordChangeEvent::BEFORE_PASSWORD_RESET
            )
            ->willReturnCallback(function (PasswordChangeEvent $event) {
                $event->disablePasswordChange('Test reason');
                return $event;
            });

        self::assertFalse($this->placeholderFilter->isPasswordResetEnabled($user));
    }
}
