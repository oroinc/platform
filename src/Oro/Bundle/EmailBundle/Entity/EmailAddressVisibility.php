<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Email addresses visibility. Collected information for each known email address.
 *
 * @ORM\Table(name="oro_email_address_visibility")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class EmailAddressVisibility
{
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @ORM\Id
     */
    private $email;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $organization;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_visible", type="boolean")
     */
    private $isVisible;

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
