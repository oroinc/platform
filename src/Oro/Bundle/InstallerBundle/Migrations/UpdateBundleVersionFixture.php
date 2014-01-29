<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\InstallerBundle\Entity\BundleVersion;

class UpdateBundleVersionFixture extends AbstractFixture
{
    /**
     * @var array
     */
    protected $bundleVersions;

    /**
     * @var bool
     */
    protected $isDemoDataUpdate = false;

    /**
     * Set array with bundle data versions
     *
     * @param $bundleVersions
     */
    public function setBundleVersions($bundleVersions)
    {
        $this->bundleVersions = $bundleVersions;
    }

    /**
     * @param bool $isDemoDataUpdate
     */
    public function setIsDemoDataUpdate($isDemoDataUpdate)
    {
        $this->isDemoDataUpdate = $isDemoDataUpdate;
    }

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        if (!empty($this->bundleVersions)) {
            foreach ($this->bundleVersions as $bundleName => $dataVersion) {
                $entity = $manager
                    ->getRepository('OroInstallerBundle:BundleVersion')
                    ->findOneBy(['bundleName' => $bundleName]);
                if ($entity === null) {
                    $entity = new BundleVersion();
                    $entity->setBundleName($bundleName);
                }
                if ($this->isDemoDataUpdate) {
                    $entity->setDemoDataVersion($dataVersion);
                } else {
                    $entity->setDataVersion($dataVersion);
                }
                $manager->persist($entity);
                $manager->flush();
            }
        }
    }
}
