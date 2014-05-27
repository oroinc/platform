<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\EventListener;

class RequirementStub
{
    /**
     * @var string
     */
    protected $textMessage;

    /**
     * @param string $textMessage
     */
    public function __construct($textMessage)
    {
        $this->textMessage = $textMessage;
    }

    /**
     * @return string
     */
    public function getTestMessage()
    {
        return $this->textMessage;
    }
}
