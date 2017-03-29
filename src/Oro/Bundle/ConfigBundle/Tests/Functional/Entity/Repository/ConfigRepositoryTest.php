<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConfigRepositoryTest extends WebTestCase
{
    /**
     * @var ConfigRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()->get('doctrine')->getManagerForClass(Config::class)
            ->getRepository(Config::class);
    }

    public function testRemoveSection()
    {
        /** @var Config $config */
        $config = $this->repository->findOneBy([]);

        $entity = $config->getScopedEntity();
        $entityId = $config->getRecordId();

        $this->repository->deleteByEntity($entity, $entityId);

        $this->assertEmpty($this->repository->findAll());
    }
}
