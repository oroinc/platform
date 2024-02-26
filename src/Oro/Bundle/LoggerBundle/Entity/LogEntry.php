<?php

namespace Oro\Bundle\LoggerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class that represents a log entry
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_logger_log_entry')]
class LogEntry
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'message', type: Types::TEXT)]
    protected ?string $message = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'context', type: 'json_array')]
    protected $context;

    #[ORM\Column(name: 'level', type: Types::SMALLINT)]
    protected ?int $level = null;

    #[ORM\Column(name: 'channel', type: Types::STRING)]
    protected ?string $channel = null;

    #[ORM\Column(name: 'datetime', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $datetime = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'extra', type: 'json_array')]
    protected $extra;

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
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return LogEntry
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     * @return LogEntry
     */
    public function setContext(array $context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     * @return LogEntry
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     * @return LogEntry
     */
    public function setChannel(string $channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param \DateTime $datetime
     * @return LogEntry
     */
    public function setDatetime(\DateTime $datetime)
    {
        $this->datetime = $datetime;
        return $this;
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param array $extra
     * @return LogEntry
     */
    public function setExtra(array $extra)
    {
        $this->extra = $extra;
        return $this;
    }
}
