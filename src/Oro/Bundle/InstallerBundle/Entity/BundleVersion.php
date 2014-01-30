<?php
namespace Oro\Bundle\InstallerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class BundleVersion
 * @ORM\Table("oro_installer_bundle_version")
 * @ORM\Entity()
 */
class BundleVersion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="bundle_name", type="string", length=150)
     */
    protected $bundleName;

    /**
     * @var string
     *
     * @ORM\Column(name="data_version", type="string", length=15, nullable=true)
     */
    protected $dataVersion;

    /**
     * @var string
     *
     * @ORM\Column(name="demo_data_version", type="string", length=15, nullable=true)
     */
    protected $demoDataVersion;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDataVersion()
    {
        return $this->dataVersion;
    }

    /**
     * @return string
     */
    public function getDemoDataVersion()
    {
        return $this->demoDataVersion;
    }

    /**
     * @return string
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }

    /**
     * @param $dataVersion
     * @return $this
     */
    public function setDataVersion($dataVersion)
    {
        $this->dataVersion = $dataVersion;
        return $this;
    }

    /**
     * @param $bundleName
     * @return $this
     */
    public function setBundleName($bundleName)
    {
        $this->bundleName = $bundleName;
        return $this;
    }

    /**
     * @param $dataVersion
     * @return $this
     */
    public function setDemoDataVersion($dataVersion)
    {
        $this->demoDataVersion = $dataVersion;
        return $this;
    }
}
