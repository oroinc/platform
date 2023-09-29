<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures;

use Doctrine\ORM\Mapping as ORM;

class TestEntityWithMagicHiddenField
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $hidden;

    public function __construct(?int $id = null, ?string $hidden = null)
    {
        $this->id = $id;
        $this->hidden = $hidden;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function __isset($name)
    {
        return 'hidden' === $name;
    }

    public function __get($name)
    {
        if ('hidden' !== $name) {
            throw new \Error(sprintf('The property "%s" does not exist or no access to it.', $name));
        }

        return $this->hidden;
    }

    public function __set($name, $value)
    {
        if ('hidden' !== $name) {
            throw new \Error(sprintf('The property "%s" does not exist or no access to it.', $name));
        }

        $this->hidden = $value;
    }
}
