<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @ORM\Entity
 */
class ManualMailboxProcessor extends MailboxProcessor
{
    const TYPE = 'manual';

    /** @var ParameterBag */
    protected $settings;
    /**
     * Returns all required setting to configure processor from this entity.
     *
     * @return ParameterBag
     */
    public function getSettings()
    {
        if ($this->settings === null) {
            return $this->settings = new ParameterBag([
                //
            ]);
        }

        return $this->settings;
    }

    /**
     * Returns type of processor.
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
