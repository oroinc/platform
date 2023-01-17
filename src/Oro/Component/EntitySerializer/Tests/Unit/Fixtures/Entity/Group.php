<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="group_table")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="name", type="string", length=50)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="label", type="string", length=255, nullable=true)
     */
    private ?string $label = null;

    /**
     * @ORM\Column(name="public", type="boolean")
     */
    private bool $public = false;

    /**
     * This field has getter and setter which not match the field name
     * and it is used to test that such fields are serialized using direct property access
     *
     * @ORM\Column(name="is_exception", type="boolean")
     */
    private bool $isException = false;

    public function __construct(int $id = null)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function isException(): bool
    {
        return $this->isException;
    }

    public function setException(bool $exception): static
    {
        $this->isException = $exception;

        return $this;
    }

    public function getComputedName(): string
    {
        return sprintf('%s (COMPUTED)', $this->name);
    }
}
