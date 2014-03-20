<?php

namespace Oro\Bundle\MigrationBundle\Migration\Loader;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\MigrationBundle\Fixture\RequestVersionFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class DataFixturesLoader extends ContainerAwareLoader
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $loadedFixtures;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param ContainerInterface $container
     */
    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        parent::__construct($container);

        $this->em = $em;
    }

    /**
     * @inheritdoc
     */
    public function getFixtures()
    {
        $fixtures = parent::getFixtures();

        // remove already loaded fixtures
        foreach ($fixtures as $key => $fixture) {
            if ($this->isFixtureAlreadyLoaded($fixture)) {
                unset($fixtures[$key]);
            }
        }

        // add a special fixture to mark new fixtures as "loaded"
        if (!empty($fixtures)) {
            $toBeLoadFixtureClassNames = [];
            foreach ($fixtures as $fixture) {
                $version = null;
                if ($fixture instanceof VersionedFixtureInterface) {
                    $version = $fixture->getVersion();
                }
                $toBeLoadFixtureClassNames[] = [
                    'fixtureClass' => get_class($fixture),
                    'version'      => $version
                ];
            }

            $updateFixture = new UpdateDataFixturesFixture();
            $updateFixture->setDataFixtures($toBeLoadFixtureClassNames);
            $fixtures[get_class($updateFixture)] = $updateFixture;
        }

        return $fixtures;
    }

    /**
     * Determines whether the given data fixture is already loaded or not
     *
     * @param object $fixtureObject
     * @return bool
     */
    protected function isFixtureAlreadyLoaded($fixtureObject)
    {
        if (!$this->loadedFixtures) {
            $this->loadedFixtures = [];

            $loadedFixtures = $this->em
                ->getRepository('OroMigrationBundle:DataFixture')
                ->findAll();
            /** @var DataFixture $fixture */
            foreach ($loadedFixtures as $fixture) {
                $this->loadedFixtures[$fixture->getClassName()] = $fixture->getVersion() ? : '';
            }
        }

        $allreadeyLoaded = false;

        if (isset($this->loadedFixtures[get_class($fixtureObject)])) {
            $allreadeyLoaded  = true;
            $dbVersion = $this->loadedFixtures[get_class($fixtureObject)];
            if ($fixtureObject instanceof VersionedFixtureInterface
                && version_compare($dbVersion, $fixtureObject->getVersion()) == -1
            ) {
                if ($fixtureObject instanceof RequestVersionFixtureInterface) {
                    $fixtureObject->setDBVersion($dbVersion);
                }
                $allreadeyLoaded = false;
            }
        }

        return $allreadeyLoaded;
    }
}
