<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Oro\Bundle\LocaleBundle\Model\NameInterface;

class AddressStub implements NameInterface
{
    /** @var string */
    private $name;

    /** @var string */
    private $organization;

    public function __construct(string $name = '', string $organization = null)
    {
        $this->name = $name;
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }
}
