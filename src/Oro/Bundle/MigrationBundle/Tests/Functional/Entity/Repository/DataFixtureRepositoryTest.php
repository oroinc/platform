<?php

namespace Oro\Bundle\MigrationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Entity\Repository\DataFixtureRepository;
use Oro\Bundle\MigrationBundle\Tests\Functional\DataFixtures\LoadDataFixtures;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DataFixtureRepositoryTest extends WebTestCase
{
    /**
     * @var DataFixtureRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadDataFixtures::class]);

        $this->repository = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(DataFixture::class);
    }

    public function testFindByClassName()
    {
        $className = 'Oro\Bundle\MigrationBundle\Migrations\Data\ORM\Fixture1';

        $actualFixtures = $this->repository->findByClassName($className);

        static::assertContains($this->getReference('fixture.1'), $actualFixtures);
    }

    public function testFindByClassNames()
    {
        $classNames = [
            'Oro\Bundle\MigrationBundle\Migrations\Data\ORM\Fixture1',
            'Oro\Bundle\MigrationBundle\Migrations\Data\ORM\Fixture2',
        ];

        $actualFixtures = $this->repository->findByClassName($classNames);

        $expectedFixtures = [
            $this->getReference('fixture.1'),
            $this->getReference('fixture.2'),
        ];

        foreach ($expectedFixtures as $expectedFixture) {
            static::assertContains($expectedFixture, $actualFixtures);
        }
    }
}
