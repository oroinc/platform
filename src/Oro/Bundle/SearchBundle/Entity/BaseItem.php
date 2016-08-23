<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\SearchBundle\Engine\Indexer;

/**
 * @ORM\MappedSuperClass
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseItem
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $entity
     *
     * @ORM\Column(name="entity", type="string", length=255)
     */
    protected $entity;

    /**
     * @var string $alias
     *
     * @ORM\Column(name="alias", type="string", length=255)
     */
    protected $alias;

    /**
     * @var integer $record_id
     *
     * @ORM\Column(name="record_id", type="integer", nullable=true)
     */
    protected $recordId;

    /**
     * @var string $title
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var bool $changed
     *
     * @ORM\Column(name="changed", type="boolean")
     */
    protected $changed = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

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
     * Set entity
     *
     * @param  string $entity
     *
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set recordId
     *
     * @param  integer $recordId
     *
     * @return $this
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Get recordId
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set changed
     *
     * @param  boolean $changed
     *
     * @return $this
     */
    public function setChanged($changed)
    {
        $this->changed = (bool)$changed;

        return $this;
    }

    /**
     * Get changed
     *
     * @return boolean
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param BaseIndexInteger $integerField
     * @return BaseItem
     */
    public function addIntegerField(BaseIndexInteger $integerField)
    {
        if (!$this->getIntegerFields()->contains($integerField)) {
            $this->getIntegerFields()->add($integerField);
        }

        return $this;
    }

    /**
     * @param BaseIndexInteger $integerField
     * @return BaseItem
     */
    public function removeIntegerField(BaseIndexInteger $integerField)
    {
        $this->getIntegerFields()->removeElement($integerField);

        return $this;
    }

    /**
     * @param BaseIndexDecimal $decimalField
     * @return BaseItem
     */
    public function addDecimalField(BaseIndexDecimal $decimalField)
    {
        if (!$this->getDecimalFields()->contains($decimalField)) {
            $this->getDecimalFields()->add($decimalField);
        }

        return $this;
    }

    /**
     * @param BaseIndexDecimal $decimalField
     * @return BaseItem
     */
    public function removeDecimalField(BaseIndexDecimal $decimalField)
    {
        $this->getDecimalFields()->removeElement($decimalField);

        return $this;
    }

    /**
     * @param BaseIndexDatetime $datetimeField
     * @return BaseItem
     */
    public function addDatetimeField(BaseIndexDatetime $datetimeField)
    {
        if (!$this->getDatetimeFields()->contains($datetimeField)) {
            $this->getDatetimeFields()->add($datetimeField);
        }

        return $this;
    }

    /**
     * @param BaseIndexDatetime $datetimeField
     * @return BaseItem
     */
    public function removeDatetimeField(BaseIndexDatetime $datetimeField)
    {
        $this->getDatetimeFields()->removeElement($datetimeField);

        return $this;
    }

    /**
     * @param BaseIndexText $textField
     * @return BaseItem
     */
    public function addTextField(BaseIndexText $textField)
    {
        if (!$this->getTextFields()->contains($textField)) {
            $this->getTextFields()->add($textField);
        }

        return $this;
    }

    /**
     * @param BaseIndexText $textField
     * @return BaseItem
     */
    public function removeTextField(BaseIndexText $textField)
    {
        $this->getTextFields()->removeElement($textField);

        return $this;
    }

    /**
     * Pre persist event listener
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event listener
     * @ORM\PreUpdate
     */
    public function beforeUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param string $alias
     * @return BaseItem
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $title
     * @return BaseItem
     */
    public function setTitle($title)
    {
        $this->title = substr($title, 0, 255);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getRecordText()
    {
        $recordText = '';
        foreach ($this->getTextFields() as $textField) {
            if ($textField->getField() == Indexer::TEXT_ALL_DATA_FIELD) {
                $recordText = $textField->getValue();
            }
        }

        return $recordText;
    }

    /**
     * @param array $objectData
     * @param Collection $fields
     * @param BaseItemFieldInterface $newRecord
     * @param string $type
     */
    protected function saveData($objectData, Collection $fields, BaseItemFieldInterface $newRecord, $type)
    {
        if (isset($objectData[$type]) && count($objectData[$type])) {
            $itemData = $objectData[$type];
            $updatedTextFields = array();
            foreach ($itemData as $fieldName => $fieldData) {
                if (!is_array($fieldData)) {
                    foreach ($fields as $index => $collectionElement) {
                        //update fields
                        if ($fieldName == $collectionElement->getField()) {
                            $collectionElement->setValue($fieldData);
                            $updatedTextFields[$index] = $index;
                            unset($itemData[$fieldName]);
                        }
                    }
                } else {
                    $this->deleteArrayFields($fields, $fieldName);
                }
            }
            //delete fields
            if (count($updatedTextFields) < count($this->getTextFields())) {
                foreach ($this->getTextFields() as $index => $collectionElement) {
                    if (!array_key_exists($index, $updatedTextFields)) {
                        $fields->removeElement($collectionElement);
                    }
                }
            }
            //add new fields
            if (isset($itemData) && count($itemData)) {
                foreach ($itemData as $fieldName => $fieldData) {
                    $this->addFieldData($fieldName, $fieldData, $fields, $newRecord);
                }
            }
        }
    }

    /**
     * @param string $fieldName
     * @param mixed $fieldData
     * @param Collection $fields
     * @param BaseItemFieldInterface $newRecord
     */
    protected function addFieldData($fieldName, $fieldData, Collection $fields, BaseItemFieldInterface $newRecord)
    {
        if (!is_array($fieldData)) {
            $fieldData = [$fieldData];
        }

        /** @var array $fieldData */
        foreach ($fieldData as $data) {
            $record = clone $newRecord;
            $this->setFieldData($record, $fieldName, $data);
            $fields->add($record);
        }
    }

    /**
     * @param Collection $fields
     * @param string $fieldName
     */
    protected function deleteArrayFields(Collection $fields, $fieldName)
    {
        /** @var Collection $fieldsToDelete */
        $fieldsToDelete = $fields->filter(
            function ($valueEntity) use ($fieldName) {
                return $valueEntity->getField() === $fieldName;
            }
        );
        if (!empty($fieldsToDelete)) {
            foreach ($fieldsToDelete as $fieldElement) {
                $fields->removeElement($fieldElement);
            }
        }
    }

    /**
     * Set record parameters
     *
     * @param BaseItemFieldInterface $record
     * @param string $fieldName
     * @param mixed $fieldData
     * @throws \InvalidArgumentException
     */
    protected function setFieldData(BaseItemFieldInterface $record, $fieldName, $fieldData)
    {
        $record->setField($fieldName)
            ->setValue($fieldData)
            ->setItem($this);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|BaseIndexInteger[]
     */
    abstract public function getIntegerFields();

    /**
     * @return \Doctrine\Common\Collections\Collection|BaseIndexDecimal[]
     */
    abstract public function getDecimalFields();

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|BaseIndexDatetime[]
     */
    abstract public function getDatetimeFields();

    /**
     * Get text fields
     *
     * @return \Doctrine\Common\Collections\Collection|BaseIndexText[]
     */
    abstract public function getTextFields();

    /**
     * Save index item data
     *
     * @param array $objectData
     * @return BaseItem
     */
    abstract public function saveItemData($objectData);
}
