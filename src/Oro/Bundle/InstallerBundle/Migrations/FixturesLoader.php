<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Doctrine\Common\DataFixtures\Loader;

use Oro\Bundle\InstallerBundle\Entity\BundleVersion;
use Oro\Bundle\InstallerBundle\Migrations\UpdateBundleVersionFixture;

class FixturesLoader extends Loader
{
    const FIXTURES_PATH           = 'DataFixtures/Migrations/ORM';
    const DEMO_DATA_FIXTURES_PATH = 'DataFixtures/DemoData/Migrations/ORM';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $fixturesDirs = [];

    /**
     * @var array
     */
    protected $bundleDataVersions = [];

    /**
     * @var bool
     */
    protected $loadDemoData = false;

    /**
     * @param EntityManager $em
     * @param KernelInterface $kernel
     */
    public function __construct(EntityManager $em, KernelInterface $kernel)
    {
        $this->em     = $em;
        $this->kernel = $kernel;
    }

    /**
     * @param bool $loadDemoData
     */
    public function isLoadDemoData($loadDemoData = false)
    {
        $this->loadDemoData = $loadDemoData;
    }

    /**
     * @inheritdoc
     */
    public function getFixtures()
    {
        if (empty($this->fixturesDirs)) {
            $this->getFixturePath();
        }
        $fixtures = [];
        foreach ($this->fixturesDirs as $fixtureDir) {
            $fixtures = array_merge($fixtures, $this->orderFixtures($this->loadFromDirectory($fixtureDir)));
        }
        // add update bundle data version fixture
        if (!empty($this->bundleDataVersions)) {
            $updateFixture = new UpdateBundleVersionFixture();
            $updateFixture->setBundleVersions($this->bundleDataVersions);
            $updateFixture->setIsDemoDataUpdate($this->loadDemoData);
            $fixtures[] = $updateFixture;
        }

        return $fixtures;
    }

    /**
     * Get list of fixtures paths to run
     *
     * @return array
     *   [
     *     [ list of sorted paths ]
     *     [ new bundles data versions. key - bundle name, value - new data version]
     *   ]
     */
    public function getFixturePath()
    {
        $repo               = $this->em->getRepository('OroInstallerBundle:BundleVersion');
        $fixtureDirs        = [];
        $bundleDataVersions = [];
        $bundles            = $this->kernel->getBundles();
        foreach ($bundles as $bundleName => $bundle) {
            $bundlePath         = $bundle->getPath();
            $bundleFixturesPath = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $bundlePath . '/' . ($this->loadDemoData ? self::DEMO_DATA_FIXTURES_PATH : self::FIXTURES_PATH)
            );

            $finder            = new Finder();
            $bundleDirFixtures = [];
            $bundleDataVersion = null;
            try {
                $finder->directories()->depth(0)->in($bundleFixturesPath);
                /** @var SplFileInfo $directory */
                foreach ($finder as $directory) {
                    if ($bundleDataVersion === null) {
                        /** @var BundleVersion $versionData */
                        $versionData = $repo->findOneBy(['bundleName' => $bundleName]);
                        if ($versionData) {
                            $bundleDataVersion = $this->getVersionInfo(
                                $this->loadDemoData
                                ? $versionData->getDataVersion()
                                : $versionData->getDemoDataVersion()
                            );
                        } else {
                            $bundleDataVersion = false;
                        }
                    }

                    $relativePath   = $directory->getRelativePathname();
                    $fixtureVersion = $this->getVersionInfo($relativePath);
                    if (!is_array($bundleDataVersion)
                        || $this->compareVersions($bundleDataVersion, $fixtureVersion) > 0
                    ) {
                        $bundleDirFixtures[] = $relativePath;
                    }
                }
            } catch (\Exception $e) {
                //dir doesn't exists
            }

            if (!empty($bundleDirFixtures)) {
                usort($bundleDirFixtures, array($this, 'sortFixtures'));
                foreach ($bundleDirFixtures as $relativePathFixture) {
                    $fixtureDirs[] = $bundleFixturesPath . DIRECTORY_SEPARATOR . $relativePathFixture;
                }
                $bundleDataVersions[$bundleName] = array_pop($bundleDirFixtures);
            }
        }

        $this->fixturesDirs       = $fixtureDirs;
        $this->bundleDataVersions = $bundleDataVersions;

        return [
            $fixtureDirs,
            $bundleDataVersions
        ];
    }

    /**
     * Usort callback sorter for directories
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortFixtures($a, $b)
    {
        return $this->compareVersions($this->getVersionInfo($b), $this->getVersionInfo($a));
    }

    /**
     * Compare two version strings
     *
     * @param $masterVersion
     * @param $comparedVersion
     * @return int returns -1 if left version is higher, 1 if right version is higher, 0 if versions is the same
     */
    protected function compareVersions($masterVersion, $comparedVersion)
    {
        $masterVersionPart   = (int)array_shift($masterVersion);
        $comparedVersionPart = (int)array_shift($comparedVersion);
        if ($masterVersionPart > $comparedVersionPart) {
            return -1;
        } elseif ($masterVersionPart < $comparedVersionPart) {
            return 1;
        } else {
            if (empty($masterVersion) && empty($comparedVersion)) {
                return 0;
            }
            if (empty($masterVersion)) {
                return 1;
            }
            if (empty($comparedVersion)) {
                return -1;
            }

            return $this->compareVersions($masterVersion, $comparedVersion);
        }
    }

    /**
     * Get array with version parts
     *
     * @param string $pathString
     * @return array
     */
    protected function getVersionInfo($pathString)
    {
        $matches = [];
        preg_match_all('/\d+/', $pathString, $matches);

        return isset($matches[0]) ? $matches[0] : [];
    }

    /**
     * @param $fixtures
     * @return array
     */
    protected function orderFixtures($fixtures)
    {
        usort(
            $fixtures,
            function ($a, $b) {
                if ($a instanceof OrderedFixtureInterface && $b instanceof OrderedFixtureInterface) {
                    if ($a->getOrder() === $b->getOrder()) {
                        return 0;
                    }

                    return $a->getOrder() < $b->getOrder() ? -1 : 1;
                } elseif ($a instanceof OrderedFixtureInterface) {
                    return $a->getOrder() === 0 ? 0 : 1;
                } elseif ($b instanceof OrderedFixtureInterface) {
                    return $b->getOrder() === 0 ? 0 : -1;
                }

                return 0;
            }
        );

        return $fixtures;
    }
}
