<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository;
use Oro\Bundle\ConfigBundle\Tests\Functional\DataFixtures\LoadTestConfigValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConfigRepositoryTest extends WebTestCase
{
    /**
     * @var ConfigRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTestConfigValue::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getManagerForClass(Config::class)
            ->getRepository(Config::class);
    }

    public function testRemoveSection()
    {
        $this->assertNotEmpty($this->repository->findByEntity('test', 1));

        $this->repository->deleteByEntity('test', 1);

        $this->assertEmpty($this->repository->findByEntity('test', 1));
    }
}
