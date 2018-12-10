<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

/**
 * Add create date support to entities
 */
trait CreatedAtAwareTrait
{
    /**
     * @var \DateTime
     *
     * @Doctrine\ORM\Mapping\Column(name="created_at", type="datetime")
     * @Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
