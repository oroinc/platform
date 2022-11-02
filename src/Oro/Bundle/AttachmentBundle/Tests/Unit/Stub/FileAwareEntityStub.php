<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;

class FileAwareEntityStub
{
    /** @var int */
    private $id;

    /** @var File */
    private $file;

    /** @var File */
    private $image;

    /** @var string */
    private $string;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param File $file
     * @return $this
     */
    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getImage(): ?File
    {
        return $this->image;
    }

    /**
     * @param File $image
     * @return $this
     */
    public function setImage(File $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getString(): ?string
    {
        return $this->string;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setString(string $string): self
    {
        $this->string = $string;

        return $this;
    }
}
