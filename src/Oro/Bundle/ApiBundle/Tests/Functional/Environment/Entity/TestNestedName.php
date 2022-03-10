<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

class TestNestedName
{
    private ?string $firstName;
    private ?string $lastName;
    private ?\DateTimeInterface $contactedAt;

    public function __construct(
        string $firstName = null,
        string $lastName = null,
        \DateTimeInterface $contactedAt = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->contactedAt = $contactedAt;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getContactedAt(): ?\DateTimeInterface
    {
        return $this->contactedAt;
    }

    public function setContactedAt(?\DateTimeInterface $contactedAt): void
    {
        $this->contactedAt = $contactedAt;
    }
}
