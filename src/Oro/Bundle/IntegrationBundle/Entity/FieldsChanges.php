<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Fields Changes
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_integration_fields_changes')]
#[ORM\Index(columns: ['entity_id', 'entity_class'], name: 'oro_integration_fields_changes_idx')]
class FieldsChanges
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER)]
    protected ?int $entityId = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'changed_fields', type: Types::ARRAY)]
    protected $changedFields = [];

    public function __construct(array $changedFields)
    {
        $this->changedFields  = $changedFields;
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
     * Set entityClass
     *
     * @param string $entityClass
     *
     * @return FieldsChanges
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Get entityClass
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     *
     * @return FieldsChanges
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set changedFields
     *
     * @param array $changedFields
     *
     * @return FieldsChanges
     */
    public function setChangedFields(array $changedFields)
    {
        $this->changedFields = $changedFields;

        return $this;
    }

    /**
     * Get changedFields
     *
     * @return array
     */
    public function getChangedFields()
    {
        return $this->changedFields;
    }
}
