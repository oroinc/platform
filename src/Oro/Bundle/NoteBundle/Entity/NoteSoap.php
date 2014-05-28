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
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $owner;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $message;

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
     * @param Note $note
     */
    public function soapInit($note)
    {
        $this->id        = $note->id;
        $this->owner     = $this->getEntityId($note->owner);
        $this->message   = $note->message;
        $this->updatedBy = $this->getEntityId($note->updatedBy);
        $this->createdAt = $note->createdAt;
        $this->updatedAt = $note->updatedAt;
    }

    /**
     * @param object $entity
     *
     * @return integer|null
     */
    protected function getEntityId($entity)
    {
        if ($entity) {
            return $entity->getId();
        }

        return null;
    }
}
