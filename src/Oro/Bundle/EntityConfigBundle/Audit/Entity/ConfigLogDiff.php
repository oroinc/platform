<?php

namespace Oro\Bundle\EntityConfigBundle\Audit\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Config Log Diff
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_entity_config_log_diff')]
class ConfigLogDiff
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ConfigLog::class, inversedBy: 'diffs')]
    #[ORM\JoinColumn(name: 'log_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ConfigLog $log = null;

    #[ORM\Column(name: 'class_name', type: Types::STRING, length: 100)]
    protected ?string $className = null;

    #[ORM\Column(name: 'field_name', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $fieldName = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $scope = null;

    #[ORM\Column(type: Types::TEXT)]
    protected ?string $diff = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array[] $configs
     * @return $this
     */
    public function setDiff(array $configs)
    {
        $this->diff = serialize($configs);

        return $this;
    }

    /**
     * @return array[]
     */
    public function getDiff()
    {
        return unserialize($this->diff);
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
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $fieldName
     * @return $this
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param ConfigLog $log
     * @return $this
     */
    public function setLog($log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @return ConfigLog
     */
    public function getLog()
    {
        return $this->log;
    }
}
