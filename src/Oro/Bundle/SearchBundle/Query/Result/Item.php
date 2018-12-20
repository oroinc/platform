<?php

namespace Oro\Bundle\SearchBundle\Query\Result;

use JMS\Serializer\Annotation\Type;

/**
 * Represents item of search results
 */
class Item
{
    /**
     * @var string
     * @Type("string")
     */
    protected $entityName;

    /**
     * @var string
     * @Type("string")
     */
    protected $entityLabel;

    /**
     * @var int
     * @Type("integer")
     */
    protected $recordId;

    /**
     * @var string
     */
    protected $recordTitle;

    /**
     * @var string
     */
    protected $recordUrl;

    /**
     * @var array
     */
    protected $entityConfig;

    /**
     * @var string[]
     */
    protected $selectedData = [];

    /**
     * @param string|null   $entityName
     * @param string|null   $recordId
     * @param string|null   $recordTitle
     * @param string|null   $recordUrl
     * @param array         $selectedData
     * @param array         $entityConfig
     */
    public function __construct(
        $entityName = null,
        $recordId = null,
        $recordTitle = null,
        $recordUrl = null,
        array $selectedData = [],
        array $entityConfig = []
    ) {
        $this->entityName   = $entityName;
        $this->entityLabel  = '';
        $this->recordId     = empty($recordId) ? 0 : $recordId;
        $this->recordTitle  = $recordTitle;
        $this->recordUrl    = $recordUrl;
        $this->selectedData = $selectedData;
        $this->entityConfig = $entityConfig;
    }

    /**
     * Set entity name
     *
     * @param string $entityName
     * @return Item
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Set entity label
     *
     * @param string $entityLabel
     * @return Item
     */
    public function setEntityLabel($entityLabel)
    {
        $this->entityLabel = $entityLabel;

        return $this;
    }

    /**
     * Set record id
     *
     * @param $recordId
     * @return Item
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Get entity name
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Get entity label
     *
     * @return string
     */
    public function getEntityLabel()
    {
        return $this->entityLabel;
    }

    /**
     * Get record id
     *
     * @return int
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Alias for getRecordId
     *
     * @return int
     */
    public function getId()
    {
        return $this->getRecordId();
    }

    /**
     * Set record title
     *
     * @param string $recordTitle
     *
     * @return Item
     */
    public function setRecordTitle($recordTitle)
    {
        $this->recordTitle = $recordTitle;

        return $this;
    }

    /**
     * Get record string
     *
     * @return string
     */
    public function getRecordTitle()
    {
        return $this->recordTitle;
    }

    /**
     * Set record string
     *
     * @param string $recordUrl
     *
     * @return Item
     */
    public function setRecordUrl($recordUrl)
    {
        $this->recordUrl = $recordUrl;

        return $this;
    }

    /**
     * Get record url
     *
     * @return string
     */
    public function getRecordUrl()
    {
        return $this->recordUrl;
    }

    /**
     * Get entity mapping config array
     *
     * @return array
     */
    public function getEntityConfig()
    {
        return $this->entityConfig;
    }

    /**
     * @return array
     */
    public function getSelectedData()
    {
        return $this->selectedData;
    }

    /**
     * @param array $selectedData
     * @return $this
     */
    public function setSelectedData(array $selectedData)
    {
        $this->selectedData = $selectedData;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [
            'entity_name'   => $this->entityName,
            'entity_label'   => $this->entityLabel,
            'record_id'     => $this->recordId,
            'record_string' => $this->recordTitle,
            'record_url'    => $this->recordUrl,
        ];

        if (count($this->selectedData) > 0) {
            $result['selected_data'] = $this->selectedData;
        }

        return $result;
    }
}
