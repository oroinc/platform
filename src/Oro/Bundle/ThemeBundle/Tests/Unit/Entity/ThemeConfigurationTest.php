<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ThemeBundle\Entity\Enum\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ThemeConfigurationTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $properties = [
            ['id', 1],
            ['type', ThemeConfigurationType::Storefront],
            ['name', 'test'],
            ['description', null],
            ['description', 'Test Description'],
            ['theme', 'default'],
            ['configuration', ['some' => 'data'], false],
            ['owner', new BusinessUnit()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        self::assertPropertyAccessors(new ThemeConfiguration(), $properties);
    }

    public function testPrePersist(): void
    {
        $themeConfiguration = new ThemeConfiguration();
        $themeConfiguration->prePersist();

        self::assertInstanceOf(\DateTime::class, $themeConfiguration->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $themeConfiguration->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $themeConfiguration = new ThemeConfiguration();
        $themeConfiguration->preUpdate();

        self::assertInstanceOf(\DateTime::class, $themeConfiguration->getUpdatedAt());
    }

    /**
     * @dataProvider getConfigurationOptionDataProvider
     */
    public function testGetConfigurationOption(string $optionKey, $expectedValue): void
    {
        $themeConfiguration = new ThemeConfiguration();
        $themeConfiguration->setConfiguration([
            'foo' => 'bar',
            'empty' => null,
        ]);

        self::assertSame($expectedValue, $themeConfiguration->getConfigurationOption($optionKey));
    }

    public function getConfigurationOptionDataProvider(): array
    {
        return [
            'key not exists' => [
                'optionKey' => 'not_existed_key',
                'value' => null,
            ],
            'null value' => [
                'optionKey' => 'empty',
                'value' => null,
            ],
            'value' => [
                'optionKey' => 'foo',
                'value' => 'bar',
            ],
        ];
    }

    public function testAddConfigurationOption(): void
    {
        $optionKey = 'foo';
        $oldOptionValue = 'foo';
        $newOptionValue = 'bar';

        $themeConfiguration = (new ThemeConfiguration())->setConfiguration([
            $optionKey => $oldOptionValue,
        ]);

        self::assertSame($oldOptionValue, $themeConfiguration->getConfigurationOption($optionKey));

        $themeConfiguration->addConfigurationOption($optionKey, $newOptionValue);

        self::assertSame($newOptionValue, $themeConfiguration->getConfigurationOption($optionKey));
    }

    public function testAddConfigurationOptionReplaceOption(): void
    {
        $optionKey = 'foo';
        $optionValue = 'bar';

        $themeConfiguration = (new ThemeConfiguration())
            ->addConfigurationOption($optionKey, $optionValue);

        self::assertSame($optionValue, $themeConfiguration->getConfigurationOption($optionKey));
    }

    public function testRemoveConfigurationOption(): void
    {
        $optionKey = 'option_to_remove';
        $optionValue = 'bar';

        $themeConfiguration = (new ThemeConfiguration())
            ->setConfiguration([
                $optionKey => 'bar',
            ]);

        self::assertSame($optionValue, $themeConfiguration->getConfigurationOption($optionKey));

        $themeConfiguration->removeConfigurationOption($optionKey);

        self::assertNull($themeConfiguration->getConfigurationOption($optionKey));
    }
}
