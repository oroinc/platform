<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Oro\Bundle\InstallerBundle\Entity\BundleMigration;

class FixturesLoader
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
     * @param EntityManager $em
     * @param KernelInterface $kernel
     */
    public function __construct(EntityManager $em, KernelInterface $kernel)
    {
        $this->em     = $em;
        $this->kernel = $kernel;
    }

    public function getFixturePath($demoData = false)
    {
        $fixtureDirs = [];
        $bundles     = $this->kernel->getBundles();
        //$mirationVersions = $repo->findAll();

        foreach ($bundles as $bundleName => $bundle) {
            $bundlePath         = $bundle->getPath();
            $bundleFixturesPath = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $bundlePath . '/' . ($demoData ? self::DEMO_DATA_FIXTURES_PATH : self::FIXTURES_PATH)
            );

            $finder            = new Finder();
            $bundleDirFixtures = [];

            try {
                $finder->directories()->depth(0)->in($bundleFixturesPath);
                var_dump($finder->count());
                /** @var SplFileInfo $directory */
                foreach ($finder as $directory) {
                    $relativePath = $directory->getRelativePathname();
                    $bundleDirFixtures = $this->arrayMerge(
                        $bundleDirFixtures,
                        $this->calculateArray($this->getVersionInfo($relativePath), $relativePath)
                    );
                }
            } catch (\Exception $e) {
                //dir does't exists
            }

            if (!empty($bundleDirFixtures)) {
                $bundleDirFixtures = $this->processBundleFixtures($bundleName, $bundleDirFixtures);


                var_dump($bundleDirFixtures);
            }
        }
    }

    protected function processBundleFixtures($bundleName, $bundleDirFixtures)
    {
        $repo = $this->em->getRepository('OroInstallerBundle:BundleMigration');
        /** @var BundleMigration $migrationData */
        $migrationData = $repo->findOneBy(['bundleName' => $bundleName]);

        if ($migrationData) {
            $this->updateVersions(
                $this->getVersionInfo($migrationData->getDataVersion()),
                $bundleDirFixtures
            );
        }

        return $bundleDirFixtures;
    }

    protected function updateVersions($versionArray, &$bundleDirFixtures)
    {
        $version = (int)array_pop($versionArray);
        $this->clearFixtrures($version, $bundleDirFixtures);
        unset($versionArray[0]);
        if (count($versionArray) > 0) {
            foreach ($bundleDirFixtures as &$subversionFixtures) {
                $this->updateVersions($versionArray, $subversionFixtures);
            }
        }
    }

    protected function clearFixtrures($dataVersion, &$bundleDirFixtures)
    {
        foreach (array_keys($bundleDirFixtures) as $version) {
            if ($version < $dataVersion) {
                unset($bundleDirFixtures[$version]);
            }
        }
    }

    /**
     * @param $inputArray
     * @param $value
     * @return mixed
     */
    protected function calculateArray($inputArray, $value)
    {
        $lastSegment[array_pop($inputArray)] = $value;
        unset($inputArray[count($inputArray)]);

        if (!empty($inputArray)) {
            return $this->calculateArray($inputArray, $lastSegment);
        } else {
            return $lastSegment;
        }
    }

    /**
     * @param $pathString
     * @return array
     */
    protected function getVersionInfo($pathString)
    {
        $matches = [];
        preg_match_all('/\d+/', $pathString, $matches);

        return isset($matches[0]) ? $matches[0] : [];
    }

    /**
     * Recurcive merge two arrays with numberic keys
     *
     * @param array $arr
     * @param array $ins
     * @return array
     */
    protected function arrayMerge($arr, $ins)
    {
        if (is_array($arr)) {
            if (is_array($ins)) {
                foreach ($ins as $k => $v) {
                    if (isset($arr[$k]) && is_array($v) && is_array($arr[$k])) {
                        $arr[$k] = $this->arrayMerge($arr[$k], $v);
                    } else {
                        // This is the new loop
                        while (isset($arr[$k])) {
                            $k++;
                        }
                        $arr[$k] = $v;
                    }
                }
            }
        } elseif (!is_array($arr) && (strlen($arr) == 0 || $arr == 0)) {
            $arr = $ins;
        }

        return ($arr);
    }
}
