<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Integration Transport Entity
 *
 * @package Oro\Bundle\IntegrationBundle\Entity
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_integration_transport')]
#[ORM\Index(columns: ['type'], name: 'oro_int_trans_type_idx')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 30)]
abstract class Transport
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'transport', targetEntity: Channel::class)]
    protected ?Channel $channel = null;

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
     * @return ParameterBag
     */
    abstract public function getSettingsBag();
}
