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
    public function testIsApplicableOnUserPage(?object $user, bool $expected)
    {
        $this->assertEquals(
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
    public function testIsUserApplicable(object $user, bool $expected)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals($expected, $this->placeholderFilter->isUserApplicable());
    }

    public function isUserApplicableDataProvider(): array
    {
        return [
            [new \stdClass(), false],
            [new User(), true]
        ];
    }
}
