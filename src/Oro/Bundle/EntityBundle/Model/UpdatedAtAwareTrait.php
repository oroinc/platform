<?php

namespace Oro\Bundle\EntityBundle\Model;

trait UpdatedAtAwareTrait
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $updatedAt;

    /**
     * @var bool
     */
    protected $isUpdatedAtSetted;

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->isUpdatedAtSetted = false;
        if ($updatedAt !== null) {
            $this->isUpdatedAtSetted = true;
        }

        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdatedAtSetted()
    {
        return $this->isUpdatedAtSetted;
    }
}
