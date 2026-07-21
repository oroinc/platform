<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity()]
#[ORM\Table(name: 'test_sanitizable_custom_id_column')]
#[Config()]
class TestSanitizableWithCustomIdColumn implements
    TestFrameworkEntityInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id()]
    #[ORM\Column(name: 'owner_id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $ownerId = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    protected ?string $email = null;

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function setOwnerId(?int $ownerId): self
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
