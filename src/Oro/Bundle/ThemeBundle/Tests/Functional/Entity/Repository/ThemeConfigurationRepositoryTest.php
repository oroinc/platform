<?php

namespace Oro\Bundle\ThemeBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ThemeBundle\Entity\Repository\ThemeConfigurationRepository;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Tests\Functional\DataFixtures\LoadThemeConfigurationData;

class ThemeConfigurationRepositoryTest extends WebTestCase
{
    private ThemeConfigurationRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadThemeConfigurationData::class]);

        $this->repository = $this->getRepository();
    }

    public function testGetFieldValue(): void
    {
        $themeConfiguration = $this->getReference(LoadThemeConfigurationData::THEME_CONFIGURATION_1);

        self::assertSame('default', $this->repository->getFieldValue($themeConfiguration->getId(), 'theme'));
        self::assertSame([], $this->repository->getFieldValue($themeConfiguration->getId(), 'configuration'));
    }

    private function getRepository(): ThemeConfigurationRepository
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(ThemeConfiguration::class)
            ->getRepository(ThemeConfiguration::class);
    }
}
