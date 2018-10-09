<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Engine\Indexer;

/**
 * Abstract class for an item at ORM search index
 *
 * @ORM\MappedSuperclass
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractItem
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
     * @var float
     * @ORM\Column(name="weight", type="decimal", precision=21, scale=8, nullable=false, options={"default"=1.0}))
     */
    protected $weight = 1.0;

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
     * @ORM\OneToMany(targetEntity="IndexText", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $textFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexInteger", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $integerFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexDecimal", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $decimalFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexDatetime", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $datetimeFields;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->textFields = new ArrayCollection();
        $this->integerFields = new ArrayCollection();
        $this->decimalFields = new ArrayCollection();
        $this->datetimeFields = new ArrayCollection();
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
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     * @return $this
     */
    public function setWeight(float $weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @param AbstractIndexInteger $integerField
     * @return AbstractItem
     */
    public function addIntegerField(AbstractIndexInteger $integerField)
    {
        if (!$this->getIntegerFields()->contains($integerField)) {
            $this->getIntegerFields()->add($integerField);
        }

        return $this;
    }

    /**
     * @param AbstractIndexInteger $integerField
     * @return AbstractItem
     */
    public function removeIntegerField(AbstractIndexInteger $integerField)
    {
        $this->getIntegerFields()->removeElement($integerField);

        return $this;
    }

    /**
     * @param AbstractIndexDecimal $decimalField
     * @return AbstractItem
     */
    public function addDecimalField(AbstractIndexDecimal $decimalField)
    {
        if (!$this->getDecimalFields()->contains($decimalField)) {
            $this->getDecimalFields()->add($decimalField);
        }

        return $this;
    }

    /**
     * @param AbstractIndexDecimal $decimalField
     * @return AbstractItem
     */
    public function removeDecimalField(AbstractIndexDecimal $decimalField)
    {
        $this->getDecimalFields()->removeElement($decimalField);

        return $this;
    }

    /**
     * @param AbstractIndexDatetime $datetimeField
     * @return AbstractItem
     */
    public function addDatetimeField(AbstractIndexDatetime $datetimeField)
    {
        if (!$this->getDatetimeFields()->contains($datetimeField)) {
            $this->getDatetimeFields()->add($datetimeField);
        }

        return $this;
    }

    /**
     * @param AbstractIndexDatetime $datetimeField
     * @return AbstractItem
     */
    public function removeDatetimeField(AbstractIndexDatetime $datetimeField)
    {
        $this->getDatetimeFields()->removeElement($datetimeField);

        return $this;
    }

    /**
     * @param AbstractIndexText $textField
     * @return AbstractItem
     */
    public function addTextField(AbstractIndexText $textField)
    {
        if (!$this->getTextFields()->contains($textField)) {
            $this->getTextFields()->add($textField);
        }

        return $this;
    }

    /**
     * @param AbstractIndexText $textField
     * @return AbstractItem
     */
    public function removeTextField(AbstractIndexText $textField)
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
        $this->updatedAt = clone $this->createdAt;
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
     * @return AbstractItem
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
     * @return AbstractItem
     */
    public function setTitle($title)
    {
        $this->title = mb_substr($title, 0, 255, mb_detect_encoding($title));

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
     * @param ItemFieldInterface $newRecord
     * @param string $type
     */
    protected function saveData($objectData, Collection $fields, ItemFieldInterface $newRecord, $type)
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
     * @param ItemFieldInterface $newRecord
     */
    protected function addFieldData($fieldName, $fieldData, Collection $fields, ItemFieldInterface $newRecord)
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
     * @param ItemFieldInterface $record
     * @param string $fieldName
     * @param mixed $fieldData
     * @throws \InvalidArgumentException
     */
    protected function setFieldData(ItemFieldInterface $record, $fieldName, $fieldData)
    {
        $record->setField($fieldName)
            ->setValue($fieldData)
            ->setItem($this);
    }

    /**
     * @return ArrayCollection|AbstractIndexInteger[]
     */
    public function getIntegerFields()
    {
        return $this->integerFields;
    }

    /**
     * @return ArrayCollection|AbstractIndexDecimal[]
     */
    public function getDecimalFields()
    {
        return $this->decimalFields;
    }

    /**
     * @return ArrayCollection|AbstractIndexDatetime[]
     */
    public function getDatetimeFields()
    {
        return $this->datetimeFields;
    }

    /**
     * @return ArrayCollection|AbstractIndexText[]
     */
    public function getTextFields()
    {
        return $this->textFields;
    }

    /**
     * Save index item data
     *
     * @param array $objectData
     * @return AbstractItem
     */
    abstract public function saveItemData($objectData);
}
