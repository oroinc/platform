<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Configuration\PrivilegeCategoryConfigurationProvider;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class RolePrivilegeCategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PrivilegeCategoryConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var RolePrivilegeCategoryProvider */
    private $categoryProvider;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(PrivilegeCategoryConfigurationProvider::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($value) {
                return 'translated_' . $value;
            });

        $this->categoryProvider = new RolePrivilegeCategoryProvider($this->configurationProvider, $translator);
    }

    public function testGetCategoriesWhenNoCategories()
    {
        $this->configurationProvider->expects(self::once())
            ->method('getCategories')
            ->willReturn([]);

        self::assertSame([], $this->categoryProvider->getCategories());
        // test local cache
        self::assertSame([], $this->categoryProvider->getCategories());
    }

    public function testGetCategories()
    {
        $this->configurationProvider->expects(self::once())
            ->method('getCategories')
            ->willReturn([
                'category1' => ['label' => 'category1_label', 'tab' => false, 'priority' => 2],
                'category2' => ['label' => 'category2_label', 'tab' => true, 'priority' => 3],
                'category3' => ['label' => 'category3_label', 'tab' => true, 'priority' => 1],
                'category4' => ['label' => 'category4_label', 'tab' => true, 'priority' => 4]
            ]);

        $expected = [
            new PrivilegeCategory('category3', 'category3_label', true, 1),
            new PrivilegeCategory('category1', 'category1_label', false, 2),
            new PrivilegeCategory('category2', 'category2_label', true, 3),
            new PrivilegeCategory('category4', 'category4_label', true, 4)
        ];

        self::assertEquals($expected, $this->categoryProvider->getCategories());
        // test local cache
        self::assertEquals($expected, $this->categoryProvider->getCategories());
    }

    public function testGetTabIds()
    {
        $this->configurationProvider->expects(self::once())
            ->method('getCategories')
            ->willReturn([
                'category1' => ['label' => 'category1_label', 'tab' => false, 'priority' => 2],
                'category2' => ['label' => 'category2_label', 'tab' => true, 'priority' => 3],
                'category3' => ['label' => 'category3_label', 'tab' => true, 'priority' => 1],
                'category4' => ['label' => 'category4_label', 'tab' => true, 'priority' => 4]
            ]);

        $expected = ['category3', 'category2', 'category4'];

        self::assertEquals($expected, $this->categoryProvider->getTabIds());
        // test local cache
        self::assertEquals($expected, $this->categoryProvider->getTabIds());
    }

    public function testGetTabs()
    {
        $this->configurationProvider->expects(self::once())
            ->method('getCategories')
            ->willReturn([
                'category1' => ['label' => 'category1_label', 'tab' => false, 'priority' => 2],
                'category2' => ['label' => 'category2_label', 'tab' => true, 'priority' => 3],
                'category3' => ['label' => 'category3_label', 'tab' => true, 'priority' => 1],
                'category4' => ['label' => 'category4_label', 'tab' => true, 'priority' => 4]
            ]);

        $expected = [
            ['id' => 'category3', 'label' => 'translated_category3_label'],
            ['id' => 'category2', 'label' => 'translated_category2_label'],
            ['id' => 'category4', 'label' => 'translated_category4_label']
        ];

        self::assertEquals($expected, $this->categoryProvider->getTabs());
        // test local cache
        self::assertEquals($expected, $this->categoryProvider->getTabs());
    }
}
