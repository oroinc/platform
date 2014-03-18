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
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * @var string
     * @ORM\Column(type="string", length=8)
     */
    protected $mode;

    /**
     * @var array
     *  [
     *      scope => [
     *          code => value,
     *          ...
     *      ],
     *      ...
     *  ]
     * @ORM\Column(name="values", type="array", nullable=true)
     */
    protected $values;

    /**
     * This variable is used to quick check whether a value is indexed or not
     *
     * @var array key = scope!code, value = true
     */
    private $indexedValueMap;

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
     * Gets a collection used to store indexed values
     *
     * @return ArrayCollection|ConfigModelIndexValue[]
     */
    abstract public function getIndexedValues();

    /**
     * Sets values for the given scope
     *
     * @param string $scope
     * @param array  $values
     * @param array  $indexed A list of indexed values. key = value code, value = true
     */
    public function fromArray($scope, array $values, array $indexed)
    {
        // ensure a scope initialized
        if (!isset($this->values[$scope])) {
            $this->values[$scope] = [];
        }
        // add new and update existing values
        foreach ($values as $code => $value) {
            $this->values[$scope][$code] = $value;
            if (isset($indexed[$code])) {
                $this->addToIndex($scope, $code, $value);
            } else {
                $this->removeFromIndex($scope, $code);
            }
        }
        // remove obsolete values
        foreach ($this->values[$scope] as $code => $value) {
            if (!isset($values[$code])) {
                unset($this->values[$scope][$code]);
                $this->removeFromIndex($scope, $code);
            }
        }
        // remove empty scope
        if (empty($this->values[$scope])) {
            unset($this->values[$scope]);
        }
    }

    /**
     * Gets all values of the given scope
     *
     * @param string $scope
     * @return array
     */
    public function toArray($scope)
    {
        return isset($this->values[$scope])
            ? $this->values[$scope]
            : [];
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
     * Creates an instance of ConfigModelIndexValue
     *
     * @param string $scope
     * @param string $code
     * @param mixed  $value
     * @return ConfigModelIndexValue
     */
    abstract protected function createIndexedValue($scope, $code, $value);

    /**
     * Makes a value indexed
     *
     * @param string $scope
     * @param string $code
     * @param mixed  $value
     * @return $this
     */
    protected function addToIndex($scope, $code, $value)
    {
        if (is_bool($value)) {
            $value = (int)$value;
        }
        $value = (string)$value;

        $indexedValues = $this->getIndexedValues();
        $this->ensureIndexedValueMapInitialized($indexedValues);
        $mapKey = sprintf('%s!%s', $scope, $code);
        if (isset($this->indexedValueMap[$mapKey])) {
            foreach ($indexedValues as $indexedValue) {
                if ($indexedValue->getScope() === $scope && $indexedValue->getCode() === $code) {
                    if ($indexedValue->getValue() !== $value) {
                        $indexedValue->setValue($value);
                    }
                    break;
                }
            }
        } else {
            $indexedValues->add($this->createIndexedValue($scope, $code, $value));
            $this->indexedValueMap[$mapKey] = true;
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
    protected function removeFromIndex($scope, $code)
    {
        $indexedValues = $this->getIndexedValues();
        $this->ensureIndexedValueMapInitialized($indexedValues);
        $mapKey = sprintf('%s!%s', $scope, $code);
        if (isset($this->indexedValueMap[$mapKey])) {
            foreach ($indexedValues as $indexKey => $indexedValue) {
                if ($indexedValue->getScope() === $scope && $indexedValue->getCode() === $code) {
                    $indexedValues->remove($indexKey);
                    unset($this->indexedValueMap[$mapKey]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Makes sure $this->indexedValueMap variable initialized
     *
     * @param ArrayCollection $indexedValues
     */
    private function ensureIndexedValueMapInitialized($indexedValues)
    {
        if (!$this->indexedValueMap) {
            $this->indexedValueMap = [];
            /** @var ConfigModelIndexValue[] $indexedValues */
            foreach ($indexedValues as $indexedValue) {
                $this->indexedValueMap[sprintf('%s!%s', $indexedValue->getScope(), $indexedValue->getCode())] = true;
            }
        }
    }

}
