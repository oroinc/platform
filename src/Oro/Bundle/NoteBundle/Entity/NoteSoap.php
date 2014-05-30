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
     * @Soap\ComplexType("Oro\Bundle\NoteBundle\Entity\EntityId")
     */
    protected $entityId;

    /**
     * @param Note $note
     */
    public function soapInit($note)
    {
        $this->id        = $note->id;
        $this->message   = $note->message;

        $entityId = new EntityId();
        $entityId->setEntity('Test');
        $entityId->setId(1);

        $this->entityId  = $entityId;
        //$this->entityId  = $note->

        $this->owner     = $this->getAssociationId($note->owner);
        $this->updatedBy = $this->getAssociationId($note->updatedBy);
        $this->createdAt = $note->createdAt;
        $this->updatedAt = $note->updatedAt;
    }

    /**
     * @return EntityId
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param object $entity
     *
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
