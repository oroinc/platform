<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

class OrderStub
{
    /** @var UserStub */
    private $user;

    /** @var CustomerStub */
    private $customer;

    /** @var string */
    private $email;

    public function __construct(UserStub $user, CustomerStub $customer, string $email = '')
    {
        $this->user = $user;
        $this->customer = $customer;
        $this->email = $email;
    }

    /**
     * @param UserStub $user
     * @return OrderStub
     */
    public function setUser(UserStub $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return UserStub
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param CustomerStub $customer
     * @return OrderStub
     */
    public function setCustomer(CustomerStub $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return CustomerStub
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}
