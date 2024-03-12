<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Email addresses visibility. Collected information for each known email address.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_email_address_visibility')]
#[ORM\HasLifecycleCallbacks]
class EmailAddressVisibility
{
    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    #[ORM\Id]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\Id]
    private ?Organization $organization = null;

    #[ORM\Column(name: 'is_visible', type: Types::BOOLEAN)]
    private ?bool $isVisible = null;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): void
    {
        $this->isVisible = $isVisible;
    }
}
