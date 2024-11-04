<?php

namespace Oro\Bundle\AttachmentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroAttachmentBundle_Entity_FileItem;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\FormBundle\Entity\EmptyItem;

/**
 * Entity for Multiple Files and Multiple Images relations
 *
 * @mixin OroAttachmentBundle_Entity_FileItem
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_attachment_file_item')]
#[Config]
class FileItem implements EmptyItem, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: File::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?File $file = null;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    protected ?int $sortOrder = 0;

    #[\Override]
    public function __toString(): string
    {
        return (string)$this->getId();
    }

    #[\Override]
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
