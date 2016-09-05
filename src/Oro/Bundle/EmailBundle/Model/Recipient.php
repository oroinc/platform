<?php

namespace Oro\Bundle\EmailBundle\Model;

class Recipient
{
    /** @var string */
    protected $email;

    /** @var string */
    protected $name;

    /** @var RecipientEntity|null */
    protected $entity;

    /**
     * @param string $email
     * @param string $name
     * @param RecipientEntity|null $entity
     */
    public function __construct($email, $name, RecipientEntity $entity = null)
    {
        $this->email = $email;
        $this->name = trim($name);
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if ($this->entity && false !== $start = strrpos($this->entity->getLabel(), '(')) {
            $label = substr($this->entity->getLabel(), $start);
            $name = $this->getFormattedName($this->name);

            return sprintf('%s %s', $name, $label);
        }

        return $this->name;
    }

    /**
     * Email should be in brackets < > for identification and extracting 'pure' email from label with additional text
     *
     * @param string $name
     *
     * @return string
     */
    public function getFormattedName($name)
    {
        $addrPos = strrpos($name, '<');
        $atPos = strrpos($name, '@');
        if ($addrPos === false && $atPos) {
            return sprintf('<%s>', $name);
        }

        return $name;
    }

    /**
     * @return string
     */
    public function getBasicNameWithOrganization()
    {
        $name = sprintf('%s|', $this->name);
        if (!$this->entity) {
            return $name;
        }

        return sprintf('%s%s', $name, $this->entity->getOrganization());
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        if (!$this->entity || !$this->entity->getOrganization()) {
            return $this->getName();
        }

        $name = $this->getFormattedName($this->name);

        return sprintf('%s %s', $name, $this->entity->getAdditionalInfo());
    }

    /**
     * @return RecipientEntity|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        if (!$this->entity) {
            return $this->email;
        }

        return sprintf('%s|%s|%s', $this->name, $this->entity->getClass(), $this->entity->getOrganization());
    }
}
