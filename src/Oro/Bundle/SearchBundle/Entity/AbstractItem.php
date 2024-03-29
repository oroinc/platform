<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Engine\Indexer;

/**
 * Abstract class for an item at ORM search index
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
#[ORM\MappedSuperclass]
abstract class AbstractItem
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'entity', type: Types::STRING, length: 255)]
    protected ?string $entity = null;

    #[ORM\Column(name: 'alias', type: Types::STRING, length: 255)]
    protected ?string $alias = null;

    #[ORM\Column(name: 'record_id', type: Types::INTEGER, nullable: true)]
    protected ?int $recordId = null;

    /**
     * @var float
     */
    #[ORM\Column(
        name: 'weight',
        type: Types::DECIMAL,
        precision: 8,
        scale: 4,
        nullable: false,
        options: ['default' => '1.0']
    )]
    protected $weight = 1.0;

    #[ORM\Column(name: 'changed', type: Types::BOOLEAN)]
    protected ?bool $changed = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, IndexText>
     */
    #[ORM\OneToMany(mappedBy: 'item', targetEntity: 'IndexText', cascade: ['all'], orphanRemoval: true)]
    protected ?Collection $textFields = null;

    /**
     * @var Collection<int, IndexInteger>
     */
    #[ORM\OneToMany(mappedBy: 'item', targetEntity: 'IndexInteger', cascade: ['all'], orphanRemoval: true)]
    protected ?Collection $integerFields = null;

    /**
     * @var Collection<int, IndexDecimal>
     */
    #[ORM\OneToMany(mappedBy: 'item', targetEntity: 'IndexDecimal', cascade: ['all'], orphanRemoval: true)]
    protected ?Collection $decimalFields = null;

    /**
     * @var Collection<int, IndexDatetime>
     */
    #[ORM\OneToMany(mappedBy: 'item', targetEntity: 'IndexDatetime', cascade: ['all'], orphanRemoval: true)]
    protected ?Collection $datetimeFields = null;

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
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event listener
     */
    #[ORM\PreUpdate]
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
