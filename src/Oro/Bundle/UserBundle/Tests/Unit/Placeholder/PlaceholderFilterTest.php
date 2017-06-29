<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Bundle\UserBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var PlaceholderFilter */
    protected $placeholderFilter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->placeholderFilter = new PlaceholderFilter($this->tokenAccessor);
    }

    protected function tearDown()
    {
        unset($this->placeholderFilter, $this->tokenAccessor);
    }

    /**
     * @param User $user
     * @param bool $expected
     * @dataProvider dataProvider
     */
    public function testIsApplicableOnUserPage($user, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->placeholderFilter->isPasswordManageEnabled($user)
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
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
     *
     * @param object $user
     * @param bool $expected
     */
    public function testIsUserApplicable($user, $expected)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals($expected, $this->placeholderFilter->isUserApplicable());
    }

    /**
     * @return array
     */
    public function isUserApplicableDataProvider()
    {
        return [
            [new \stdClass(), false],
            [new User(), true]
        ];
    }
}
