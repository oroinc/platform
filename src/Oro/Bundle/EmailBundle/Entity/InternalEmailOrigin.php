<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An Email Origin which can be used for emails sent by BAP
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class InternalEmailOrigin extends EmailOrigin
{
    public const BAP = 'BAP';

    public const MAILBOX_NAME = 'Local';

    #[ORM\Column(name: 'internal_name', type: Types::STRING, length: 30)]
    protected ?string $internalName = null;

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
    #[\Override]
    public function __toString()
    {
        return sprintf('Internal - %s', $this->internalName);
    }

    #[ORM\PrePersist]
    public function beforeSave()
    {
        if ($this->mailboxName === null) {
            $this->mailboxName = self::MAILBOX_NAME;
        }
    }
}
