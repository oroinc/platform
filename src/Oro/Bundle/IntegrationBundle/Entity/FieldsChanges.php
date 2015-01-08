<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *      name="oro_integration_fields_changes",
 *      indexes={
 *          @ORM\Index(name="oro_integration_fields_changes_idx", columns={"entity_id", "entity_class"})
 *      }
 * )
 * @ORM\Entity()
 */
class FieldsChanges
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    protected $entityClass;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_id", type="integer")
     */
    protected $entityId;

    /**
     * @var array
     *
     * @ORM\Column(name="changed_fields", type="array")
     */
    protected $changedFields = [];

    /**
     * @param array $changedFields
     */
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
