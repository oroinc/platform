<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Translation\TranslatorInterface;

class RolePrivilegeCategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RolePrivilegeCategoryProvider */
    private $categoryProvider;

    protected function setUp()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $this->categoryProvider = new RolePrivilegeCategoryProvider($translator);
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

    /**
     * @dataProvider getPermissionCategoriesDataProvider
     *
     * @param PrivilegeCategory[] $categories
     * @param PrivilegeCategory[] $expected
     */
    public function testGetPermissionCategories(array $categories, array $expected)
    {
        $this->setCategories($categories);
        $this->assertEquals($expected, $this->categoryProvider->getPermissionCategories());
    }

    /**
     * @return array
     */
    public function getPermissionCategoriesDataProvider()
    {
        $categoryVisible = new PrivilegeCategory('category1', '', true, 1);
        $categoryNotVisible = new PrivilegeCategory('category2', '', true, 1);
        $categoryNotVisible->setVisible(false);

        return [
            'no categories' => [
                'categories' => [],
                'expected' => [],
            ],
            'with categories' => [
                'categories' => [$categoryVisible, $categoryNotVisible],
                'expected' => [$categoryVisible],
            ],
        ];
    }

    /**
     * @param PrivilegeCategory[] $categories
     */
    private function setCategories(array $categories)
    {
        $this->setValue($this->categoryProvider, 'categoryList', $categories);
    }
}
