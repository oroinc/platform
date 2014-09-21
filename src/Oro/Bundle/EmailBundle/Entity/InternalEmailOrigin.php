<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

/**
 * An Email Origin which cam be used for emails sent by BAP
 *
 * @ORM\Entity
 */
class InternalEmailOrigin extends EmailOrigin
{
    const BAP = 'BAP';

    /**
     * @var string
     *
     * @ORM\Column(name="internal_name", type="string", length=30)
     * @Soap\ComplexType("string")
     * @JMS\Type("string")
     */
    protected $internalName;

    /**
     * Get an internal email origin name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->internalName;
    }

    /**
     * Set an internal email origin name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->internalName = $name;

        return $this;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('Internal - %s', $this->internalName);
    }
}
