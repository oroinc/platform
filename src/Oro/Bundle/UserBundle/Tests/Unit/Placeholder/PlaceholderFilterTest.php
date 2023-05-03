<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var PlaceholderFilter */
    private $placeholderFilter;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->placeholderFilter = new PlaceholderFilter($this->tokenAccessor);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsApplicableOnUserPage(?object $user, bool $expected): void
    {
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
}
