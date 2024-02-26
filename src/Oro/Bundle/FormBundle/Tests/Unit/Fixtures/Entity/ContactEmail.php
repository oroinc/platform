<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;

#[ORM\Entity]
class ContactEmail implements PrimaryItem
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $email = null;

    #[ORM\Column(name: 'is_primary', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $primary = null;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Contact $owner = null;

    /**
     * {@inheritdoc}
     */
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimary($value)
    {
        $this->primary = $value;
    }
}
