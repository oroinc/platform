<?php

namespace Oro\Bundle\EntityConfigBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class AbstractConfigModel
{
    /**
     * @var \DateTime $created
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * @var string $updated
     * @ORM\Column(type="string", length=8)
     */
    protected $mode;

    /**
     * @var array key = scope!code
     * @ORM\Column(name="values", type="array", nullable=true)
     */
    protected $values;

    /**
     * @var ConfigModelIndexValue[]|ArrayCollection
     */
    protected $indexedValues;

    /**
     * @param string $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param \DateTime $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $updated
     * @return $this
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param       $scope
     * @param array $values
     * @param array $indexedValues
     */
    public function fromArray($scope, array $values, array $indexedValues)
    {
        // add new and update existing values
        foreach ($values as $code => $value) {
            $this->values[sprintf('%s!%s', $scope, $code)] = $value;
            if (isset($indexedValues[$code])) {
                $this->addToIndex($scope, $code, $value);
            } else {
                $this->removeFromIndex($scope, $code);
            }
        }
        // remove obsolete values
        foreach ($this->values as $key => $value) {
            $pair = explode('!', $key);
            if ($scope === $pair[0] && !isset($values[$pair[1]])) {
                unset($this->values[$key]);
                $this->removeFromIndex($scope, $pair[1]);
            }
        }
    }

    /**
     * @param string $scope
     * @return array
     */
    public function toArray($scope)
    {
        $result = [];
        foreach ($this->values as $key => $value) {
            $pair = explode('!', $key);
            if ($scope === $pair[0]) {
                $result[$pair[1]] = $value;
            }
        }

        return $result;
    }

    /**
     * Makes a value indexed
     *
     * @param string $scope
     * @param string $code
     * @param mixed  $value
     * @return $this
     */
    public function addToIndex($scope, $code, $value)
    {
        if (is_bool($value)) {
            $value = (int)$value;
        }

        $existingIndexedValue = null;
        foreach ($this->indexedValues as $indexedValue) {
            if ($indexedValue->getScope() === $scope && $indexedValue->getCode() === $code) {
                $existingIndexedValue = $indexedValue;
                break;
            }
        }
        if ($existingIndexedValue) {
            if ($existingIndexedValue->getValue() !== $value) {
                $existingIndexedValue->setValue($value);
            }
        } else {
            $this->indexedValues->add($this->createIndexedValue($scope, $code, $value));
        }

        return $this;
    }

    /**
     * Makes a value un-indexed
     *
     * @param string $scope
     * @param string $code
     * @return $this
     */
    public function removeFromIndex($scope, $code)
    {
        foreach ($this->indexedValues as $key => $indexedValue) {
            if ($indexedValue->getScope() === $scope && $indexedValue->getCode() === $code) {
                $this->indexedValues->remove($key);
                break;
            }
        }

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created = $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Creates an instance of ConfigModelValue
     *
     * @param string $scope
     * @param string $code
     * @param mixed  $value
     * @return ConfigModelIndexValue
     */
    abstract protected function createIndexedValue($scope, $code, $value);
}
