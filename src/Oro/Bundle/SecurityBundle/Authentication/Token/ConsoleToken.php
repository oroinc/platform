<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class ConsoleToken extends AbstractToken implements OrganizationContextTokenInterface
{
    /**
     * @var Organization
     */
    private $organization;

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return ''; // anonymous credentials
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizationContext()
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrganizationContext(Organization $organization)
    {
        $this->organization = $organization;
    }
}
