<?php


namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;

class CategoryProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var RolePrivilegeCategoryProvider */
    private $categoryProvider;

    protected function setUp()
    {
        $this->categoryProvider = new RolePrivilegeCategoryProvider();
    }

    public function testCategoryListFetchSuccess()
    {
        $this->markTestIncomplete(
            'This test have to be rewritten because of interface changes'
        );
        $this->assertEquals(
            [
                'account_management' => [
                    'label' => 'Account Management',
                    'tab' => true
                ],
                'marketing' => [
                    'label' => 'Marketing',
                    'tab' => true
                ],
                'sales_data' => [
                    'label' => 'Sales Data',
                    'tab' => true
                ],
                'address' => [
                    'label' => 'Address',
                    'tab' => false
                ],
                'calendar' => [
                    'label' => 'Calendar',
                    'tab' => false
                ]
            ],
            $this->categoryProvider->getPermissionCategories()
        );
    }
}
