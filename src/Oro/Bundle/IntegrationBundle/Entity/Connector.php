<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Transport
 *
 * @package Oro\Bundle\IntegrationBundle\Entity
 * @ORM\Table(name="oro_integration_connector")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=30)
 */
abstract class Connector
{
    /**
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Channel
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Channel", inversedBy="connectors")
     */
    protected $channel;

    /**
     * @var Transport
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Transport")
     */
    protected $transport;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Channel $channel
     *
     * @return $this
     */
    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Transport $transport
     *
     * @return $this
     */
    public function setTransport(Transport $transport)
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * @return Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }
}
