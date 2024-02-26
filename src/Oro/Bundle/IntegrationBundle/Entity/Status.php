<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Integration Status ORM entity.
 *
 * @package Oro\Bundle\IntegrationBundle\Entity
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_integration_channel_status')]
#[ORM\Index(columns: ['connector', 'code'], name: 'oro_intch_con_state_idx')]
#[ORM\Index(columns: ['date'], name: 'oro_intch_date_idx')]
class Status
{
    public const STATUS_COMPLETED = '1';
    public const STATUS_FAILED    = '2';

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class, cascade: ['ALL'], inversedBy: 'statuses')]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Channel $channel = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 255)]
    protected ?string $code = null;

    #[ORM\Column(name: 'connector', type: Types::STRING, length: 255)]
    protected ?string $connector = null;

    #[ORM\Column(name: 'message', type: Types::TEXT)]
    protected ?string $message = null;

    #[ORM\Column(name: 'date', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $date = null;

    /**
     * @var array $data
     */
    #[ORM\Column(name: 'data', type: 'json_array', nullable: true)]
    protected $data;

    public function __construct()
    {
        $this->setDate(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @return int
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
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $connector
     *
     * @return $this
     */
    public function setConnector($connector)
    {
        $this->connector = $connector;

        return $this;
    }

    /**
     * @return string
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Do not serialize
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * @param array $data
     *
     * @return Status
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getCode();
    }
}
