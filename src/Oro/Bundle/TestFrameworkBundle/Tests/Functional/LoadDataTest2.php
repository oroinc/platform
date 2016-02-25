<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;

/**
 * @dbIsolation
 */
class LoadDataTest extends WebTestCase
{
    const DEMO_FIXTURE_PATH = '/Migrations/Data/Demo/ORM';

    /**
     * @var ORMExecutor
     */
    protected $executor;

    /**
     * @param FixtureInterface $fixture
     *
     * @dataProvider loadDemoDataProvider
     */
    public function testLoadDemoData($fixture)
    {
        $this->initClient();

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $executor = new ORMExecutor($em);

        try {
            $executor->execute([$fixture], true);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function loadDemoDataProvider()
    {
        return $this->prepareFixtures(self::DEMO_FIXTURE_PATH);
    }

    /**
     * @param string $path
     * @return array
     */
    protected function prepareFixtures($path)
    {
        $this->initClient([], [], true);

        $fixtures = $this->getFixtures($path, $found);

        if (!$found || $found > count($fixtures)) {
            static::markTestSkipped(
                sprintf(
                    'Found %d fixtures, but %d already loaded',
                    $found,
                    $found - count($fixtures)
                )
            );
        }

        $data = [];

        foreach ($fixtures as $fixture) {
            $data[] = [
                'fixture' => $fixture,
            ];
        };

        return $data;
    }

    /**
     * @var string $fixturePath
     * @var int $found
     * @return FixtureInterface[]
     */
    protected function getFixtures($fixturePath, &$found = 0)
    {
        /* @var $loader DataFixturesLoader */
        $loader = $this->getContainer()->get('oro_migration.data_fixtures.loader');

        foreach (static::$kernel->getBundles() as $bundle) {
            $path = $bundle->getPath() . $fixturePath;
            if (is_dir($path)) {
                $found += count($loader->loadFromDirectory($path));
            }
        }

        return $loader->getFixtures();
    }
}
