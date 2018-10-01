<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Model for populating from part to \Swift_Message.
 */
class From
{
    /**
     * @var array
     */
    private $params;

    /**
     * @param array $params
     */
    private function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @param \Swift_Message $message
     */
    public function populate(\Swift_Message $message): void
    {
        $message->setFrom(...$this->params);
    }

    /**
     * Returns array representation.
     * @return array
     */
    public function toArray(): array
    {
        return $this->params;
    }

    /**
     * Used when single email address should be defined in From header.
     *
     * @param string $emailAddress
     * @param string|null $name
     * @return From
     */
    public static function emailAddress(string $emailAddress, string $name = null): self
    {
        return new self([$emailAddress, $name]);
    }

    /**
     * Used when multiple email addresses are defined in From header which is possible according to RFC2822.
     *
     * @param array $emailAddresses a list of email addresses or [email address1 => name1, email address2 => name2, ...]
     *
     * @return From
     */
    public static function emailAddresses(array $emailAddresses): self
    {
        return new self([$emailAddresses]);
    }

    /**
     * Creates object based on array returned by toArray method.
     * @param array $data
     * @return From
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
