<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

class MassActionResponse implements MassActionResponseInterface
{
    /** @var boolean */
    protected $successful;

    /**  @var string */
    protected $message;

    /** @var array */
    protected $options = [];

    /**
     * @param boolean $successful
     * @param string  $message
     * @param array   $options
     */
    public function __construct($successful, $message, array $options = [])
    {
        $this->successful = $successful;
        $this->message    = $message;
        $this->options    = $options;
    }

    #[\Override]
    public function getOptions()
    {
        return $this->options;
    }

    #[\Override]
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * @return boolean
     */
    #[\Override]
    public function isSuccessful()
    {
        return $this->successful;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMessage()
    {
        return $this->message;
    }
}
