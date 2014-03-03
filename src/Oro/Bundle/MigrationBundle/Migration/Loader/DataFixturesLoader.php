<?php

namespace Oro\Bundle\MigrationBundle\Migration\Loader;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture;

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
     * @param EntityManager      $em
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
            if ($this->isFixtureAlreadyLoaded(get_class($fixture))) {
                unset($fixtures[$key]);
            }
        }

        // add a special fixture to mark new fixtures as "loaded"
        if (!empty($fixtures)) {
            $toBeLoadFixtureClassNames = [];
            foreach ($fixtures as $fixture) {
                $toBeLoadFixtureClassNames[] = get_class($fixture);
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
     * @param string $className
     * @return bool
     */
    protected function isFixtureAlreadyLoaded($className)
    {
        if (!$this->loadedFixtures) {
            $this->loadedFixtures = [];

            $loadedFixtures = $this->em
                ->getRepository('OroMigrationBundle:DataFixture')
                ->findAll();
            /** @var DataFixture $fixture */
            foreach ($loadedFixtures as $fixture) {
                $this->loadedFixtures[$fixture->getClassName()] = true;
            }
        }

        return isset($this->loadedFixtures[$className]);
    }
}
