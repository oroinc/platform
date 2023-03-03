<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\FormBundle\Entity\EmptyItem;

/**
 * Entity for Multiple Files and Multiple Images relations
 *
 * @ORM\Table(name="oro_attachment_file_item")
 * @ORM\Entity()
 * @Config
 */
class FileItem implements EmptyItem, ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var File|null
     *
     * @ORM\OneToOne(
     *     targetEntity="Oro\Bundle\AttachmentBundle\Entity\File",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     *  )
     * @ORM\JoinColumn(name="file_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $file;

    /**
     * @var int|null
     *
     * @ORM\Column(name="sort_order", type="integer", options={"default"=0})
     */
    protected $sortOrder = 0;

    public function __toString(): string
    {
        return (string)$this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return null === $this->getFile();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param File|null $file
     * @return $this
     */
    public function setFile(?File $file)
    {
        $this->file = $file;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * @param int|null $order
     * @return $this
     */
    public function setSortOrder(?int $order)
    {
        $this->sortOrder = $order;

        return $this;
    }
}
