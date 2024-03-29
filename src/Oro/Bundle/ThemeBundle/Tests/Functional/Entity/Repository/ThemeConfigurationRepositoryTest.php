<?php

namespace Oro\Bundle\ThemeBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ThemeBundle\Entity\Repository\ThemeConfigurationRepository;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Tests\Functional\DataFixtures\LoadThemeConfigurationData;

class ThemeConfigurationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadThemeConfigurationData::class]);
    }

    /**
     * @dataProvider getThemeByThemeConfigurationIdDataProvider
     */
    public function testGetThemeByThemeConfigurationId(
        ?string $expectedTheme,
        ?int $themeConfigurationId = null,
        ?string $themeConfigurationReferenceName = null,
    ): void {
        $themeConfigurationId = $themeConfigurationReferenceName
            ? $this->getReference($themeConfigurationReferenceName)->getId()
            : $themeConfigurationId;

        self::assertEquals(
            $expectedTheme,
            $this->getRepository()->getThemeByThemeConfigurationId($themeConfigurationId)
        );
    }

    public function getThemeByThemeConfigurationIdDataProvider(): array
    {
        return [
            [
                'expectedTheme' => null,
                'themeConfigurationId' => null,
            ],
            [
                'expectedTheme' => null,
                'themeConfigurationId' => \PHP_INT_MAX,
            ],
            [
                'expectedTheme' => 'default',
                'themeConfigurationId' => null,
                'themeConfigurationReferenceName' => LoadThemeConfigurationData::THEME_CONFIGURATION_1,
            ],
        ];
    }

    private function getRepository(): ThemeConfigurationRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ThemeConfiguration::class);
    }
}
