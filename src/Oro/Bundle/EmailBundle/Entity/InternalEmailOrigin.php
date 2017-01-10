<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * An Email Origin which can be used for emails sent by BAP
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class InternalEmailOrigin extends EmailOrigin
{
    const BAP = 'BAP';

    const MAILBOX_NAME = 'Local';

    /**
     * @var string
     *
     * @ORM\Column(name="internal_name", type="string", length=30)
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

    /**
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        if ($this->mailboxName === null) {
            $this->mailboxName = self::MAILBOX_NAME;
        }
    }
}
