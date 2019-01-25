<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Tests\Functional\DataFixtures\LoadConfigValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ConfigValueRepositoryTest extends WebTestCase
{
    /**
     * @var ConfigValueRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadConfigValue::class,
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getManagerForClass(ConfigValue::class)
            ->getRepository(ConfigValue::class);
    }

    public function testRemoveSection(): void
    {
        $section = 'general';

        $configValues = $this->repository->findBy(['section' => $section]);

        $this->assertNotEmpty($configValues);

        $this->repository->removeBySection($section);

        $configValues = $this->repository->findBy(['section' => $section]);

        $this->assertEmpty($configValues);
    }

    public function testGetConfigValues(): void
    {
        /** @var ConfigValue $configValue */
        $configValue = $this->repository->findOneBy(['section' => 'additional']);
        $result = $this->repository->getConfigValues(
            $configValue->getConfig()->getScopedEntity(),
            $configValue->getSection(),
            $configValue->getName()
        );

        $this->assertEquals([$configValue], $result);
    }

    public function testGetConfigValueRecordIds(): void
    {
        /** @var ConfigValue $configValue */
        $configValue = $this->repository->findOneBy(['section' => 'additional']);

        $result = $this->repository->getConfigValueRecordIds(
            $configValue->getConfig()->getScopedEntity(),
            $configValue->getSection(),
            $configValue->getName()
        );

        $this->assertEquals([$configValue->getConfig()->getRecordId()], $result);
    }
}
