<?php

namespace Oro\Bundle\ActionBundle\Model;

class Parameter
{
    const NO_DEFAULT = INF;

    /* @var string */
    private $name;

    /* @var string */
    private $type;

    /* @var string */
    private $message = '';

    /* @var mixed */
    private $default = self::NO_DEFAULT;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = (string)$name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasTypeHint()
    {
        return $this->type !== null;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function hasMessage()
    {
        return $this->message !== '';
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = (string)$message;

        return $this;
    }

    /**
     * @return mixed
     * @throws \LogicException
     */
    public function getDefault()
    {
        if (!$this->hasDefault()) {
            throw new \LogicException(
                sprintf(
                    'Parameter `%s` has no default value set. Please check `%s` or `%s` before default value retrieval',
                    $this->name,
                    'hasDefault() === true',
                    'isRequired() === false'
                )
            );
        }

        return $this->default;
    }

    /**
     * @param mixed $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasDefault()
    {
        return $this->default !== self::NO_DEFAULT;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->default === self::NO_DEFAULT;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
