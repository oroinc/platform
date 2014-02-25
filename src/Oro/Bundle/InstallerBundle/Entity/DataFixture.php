<?php

namespace Oro\Bundle\InstallerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("oro_installer_data_fixtures")
 * @ORM\Entity()
 */
class DataFixture
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
     * @ORM\Column(name="class_name", type="string", length=255)
     */
    protected $className;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="loaded_at", type="datetime")
     */
    protected $loadedAt;

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
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLoadedAt()
    {
        return $this->loadedAt;
    }

    /**
     * @param \DateTime $loadedAt
     * @return $this
     */
    public function setLoadedAt($loadedAt)
    {
        $this->loadedAt = $loadedAt;

        return $this;
    }
}
