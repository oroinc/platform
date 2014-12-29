<?php

namespace Oro\Bundle\NoteBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.NoteBundle.Entity.Note")
 */
class NoteSoap extends Note implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @Soap\ComplexType("string", nillable=false)
     */
    protected $message;

    /**
     * @Soap\ComplexType("Oro\Bundle\EntityBundle\Model\EntityIdSoap", nillable=true)
     */
    protected $entityId;

    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $createdBy;

    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $updatedBy;

    /**
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $createdAt;

    /**
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $updatedAt;

    /**
     * @param NoteSoap $note
     */
    public function soapInit($note)
    {
        $this->id        = $note->id;
        $this->message   = $note->message;
        $this->entityId  = $note->entityId;
        $this->createdBy = $this->getAssociationId($note->owner);
        $this->updatedBy = $this->getAssociationId($note->updatedBy);
        $this->createdAt = $note->createdAt;
        $this->updatedAt = $note->updatedAt;
    }

    /**
     * @param object $entity
     * @return integer|null
     */
    protected function getAssociationId($entity)
    {
        if ($entity) {
            return $entity->getId();
        }
        return null;
    }
}
