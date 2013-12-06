<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class Transport
 *
 * @package Oro\Bundle\IntegrationBundle\Entity
 * @ORM\Table(name="oro_integration_transport")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=30)
 */
abstract class Transport
{
    /**
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastSyncDate;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ParameterBag
     */
    abstract public function getSettingsBag();

    /**
     * @param \DateTime $lastSyncDate
     * @return $this
     */
    public function setLastSyncDate($lastSyncDate)
    {
        $this->lastSyncDate = $lastSyncDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSyncDate()
    {
        return $this->lastSyncDate;
    }
}
