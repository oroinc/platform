<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Oro\Bundle\LocaleBundle\Model\NameInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class CustomerStub implements NameInterface
{
    /** @var string */
    private $name;

    /** @var Organization */
    private $organization;

    /**
     * @param string $name
     * @param Organization|null $organization
     */
    public function __construct($name = '', Organization $organization = null)
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

    /**
     * @return null|Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
