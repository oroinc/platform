<?php

namespace Oro\Bundle\CronBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Represents a table with CRON jobs schedules.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_cron_schedule')]
#[ORM\UniqueConstraint(name: 'UQ_COMMAND', columns: ['command', 'args_hash', 'definition'])]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-tasks'],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management']
    ]
)]
class Schedule
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'command', type: Types::STRING, length: 255)]
    protected ?string $command = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'args', type: 'json_array')]
    protected $arguments;

    #[ORM\Column(name: 'args_hash', type: Types::STRING, length: 32)]
    protected ?string $argumentsHash = null;

    #[ORM\Column(name: 'definition', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $definition = null;

    public function __construct()
    {
        $this->setArguments([]);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get command name
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set command name
     *
     * @param  string  $command
     * @return Schedule
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        sort($arguments);

        $this->arguments = $arguments;
        $this->argumentsHash = md5(json_encode($arguments));

        return $this;
    }

    /**
     * Returns cron definition string
     *
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Set cron definition string
     *
     * General format:
     * *    *    *    *    *
     * ┬    ┬    ┬    ┬    ┬
     * │    │    │    │    │
     * │    │    │    │    │
     * │    │    │    │    └───── day of week (0 - 6) (0 to 6 are Sunday to Saturday, or use names)
     * │    │    │    └────────── month (1 - 12)
     * │    │    └─────────────── day of month (1 - 31)
     * │    └──────────────────── hour (0 - 23)
     * └───────────────────────── min (0 - 59)
     *
     * Predefined values are:
     *  @yearly (or @annually)  Run once a year at midnight in the morning of January 1                 0 0 1 1 *
     *  @monthly                Run once a month at midnight in the morning of the first of the month   0 0 1 * *
     *  @weekly                 Run once a week at midnight in the morning of Sunday                    0 0 * * 0
     *  @daily                  Run once a day at midnight                                              0 0 * * *
     *  @hourly                 Run once an hour at the beginning of the hour                           0 * * * *
     *
     * @param  string  $definition New cron definition
     * @return Schedule
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }

    /**
     * @return string
     */
    public function getArgumentsHash()
    {
        return $this->argumentsHash;
    }
}
