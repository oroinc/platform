<?php

namespace Oro\Bundle\MigrationBundle\Migration\Loader;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Fixture\LoadedFixtureVersionAwareInterface;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Bundle\MigrationBundle\Migration\RenameDataFixturesFixture;
use Oro\Bundle\MigrationBundle\Migration\Sorter\DataFixturesSorter;
use Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Provides list of data fixtures to perform
 */
class DataFixturesLoader extends ContainerAwareLoader
{
    /** @var DataFixture[] */
    protected $loadedFixtures;

    /** @var \ReflectionProperty */
    protected $ref;

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityManager $em,
        protected KernelInterface $kernel,
        ContainerInterface $container
    ) {
        parent::__construct($container);
    }

    /**
     * @inheritdoc
     */
    public function getFixtures()
    {
        $sorter   = new DataFixturesSorter();
        $fixtures = $sorter->sort($this->getAllFixtures());

        $this->fillLoadedFixtures();

        $renameFixture = new RenameDataFixturesFixture();
        $updateFixture = new UpdateDataFixturesFixture();
        foreach ($fixtures as $key => $fixture) {
            $this->collectToRename($fixture, $renameFixture);
            $this->collectToLoad($fixture, $updateFixture);
        }

        $toLoad = [];

        if ($renameFixture->isNeedPerform()) {
            $toLoad[\get_class($renameFixture)] = $renameFixture;
        }

        if ($updateFixture->getFixtures()) {
            $toLoad = \array_merge($toLoad, $updateFixture->getFixtures());
            $toLoad[\get_class($updateFixture)] = $updateFixture;
        }

        return $toLoad;
    }

    /**
     * @return array
     */
    private function getAllFixtures()
    {
        if (!$this->ref) {
            $this->ref = new \ReflectionProperty('Doctrine\Common\DataFixtures\Loader', 'fixtures');
            $this->ref->setAccessible(true);
        }
        return $this->ref->getValue($this);
    }

    private function fillLoadedFixtures(): void
    {
        $this->loadedFixtures = [];

        /** @var DataFixture[] $loadedFixtures */
        $loadedFixtures = $this->em->getRepository(DataFixture::class)->findAll();

        foreach ($loadedFixtures as $fixture) {
            $this->loadedFixtures[$fixture->getClassName()] = $fixture->getVersion() ?: '0.0';
        }
    }

    /**
     * Collect fixture to rename if needed
     */
    private function collectToRename(FixtureInterface $fixtureObject, RenameDataFixturesFixture $renameFixture): void
    {
        $currentClassName = \get_class($fixtureObject);

        if (isset($this->loadedFixtures[$currentClassName]) || !$fixtureObject instanceof RenamedFixtureInterface) {
            return;
        }

        foreach ($fixtureObject->getPreviousClassNames() as $previousClassName) {
            if (isset($this->loadedFixtures[$previousClassName])) {
                $this->loadedFixtures[$currentClassName] = $this->loadedFixtures[$previousClassName];
                unset($this->loadedFixtures[$previousClassName]);

                $renameFixture->addRename($previousClassName, $currentClassName);
                return;
            }
        }
    }

    /**
     * Collect fixture to load if needed
     */
    private function collectToLoad(FixtureInterface $fixtureObject, UpdateDataFixturesFixture $updateFixture): void
    {
        $fixtureClassName = \get_class($fixtureObject);

        if (isset($this->loadedFixtures[$fixtureClassName])) {
            if (!$fixtureObject instanceof VersionedFixtureInterface
                || version_compare($this->loadedFixtures[$fixtureClassName], $fixtureObject->getVersion()) !== -1
            ) {
                return;
            }

            if ($fixtureObject instanceof LoadedFixtureVersionAwareInterface) {
                $fixtureObject->setLoadedVersion($this->loadedFixtures[$fixtureClassName]);
            }
        }

        $updateFixture->addFixture($fixtureObject);
    }
}
