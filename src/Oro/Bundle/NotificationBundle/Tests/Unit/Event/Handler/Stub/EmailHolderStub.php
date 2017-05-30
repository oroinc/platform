<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

class EmailHolderStub implements EmailHolderInterface
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var EmailHolderStub
     */
    private $holder;

    /**
     * @var EmailHolderStub[]
     */
    private $holders = [];

    /**
     * @param string $email
     */
    public function __construct($email = null)
    {
        $this->email = $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return EmailHolderStub
     */
    public function getHolder()
    {
        return $this->holder;
    }

    /**
     * @param EmailHolderStub $holder
     */
    public function setHolder($holder)
    {
        $this->holder = $holder;
    }

    /**
     * @return EmailHolderStub[]
     */
    public function getHolders()
    {
        return $this->holders;
    }

    /**
     * @param EmailHolderStub[] $holders
     */
    public function setHolders($holders)
    {
        $this->holders = $holders;
    }
}
