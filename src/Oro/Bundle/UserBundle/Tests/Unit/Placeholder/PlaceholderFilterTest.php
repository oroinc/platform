<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Placeholder;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlaceholderFilter
     */
    protected $placeholderFilter;

    protected function setUp()
    {
        $this->placeholderFilter = new PlaceholderFilter();
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
}
