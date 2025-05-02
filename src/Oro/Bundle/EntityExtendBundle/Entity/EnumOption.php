<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Entity\PriorityItem;

/**
 * Oro enum option entity.
 *
 * @property string $defaultName
 * @method string getDefaultName()
 */
#[ORM\Table(name: 'oro_enum_option')]
#[ORM\Index(columns: ['enum_code'], name: 'oro_enum_code_idx')]
#[ORM\Entity(repositoryClass: EnumOptionRepository::class)]
#[Gedmo\TranslationEntity(class: EnumOptionTranslation::class)]
#[Config]
class EnumOption implements Translatable, PriorityItem, EnumOptionInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::STRING, length: 100)]
    #[ORM\Id]
    private string $id;

    #[ORM\Column(name: 'priority', type: Types::INTEGER)]
    private int $priority = 0;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN)]
    private bool $default = false;

    #[ORM\Column(name: 'enum_code', type: Types::STRING, length: 64)]
    private string $enumCode;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Gedmo\Translatable]
    private ?string $name = null;

    #[ORM\Column(name: 'internal_id', type: Types::STRING, length: 32)]
    private string $internalId;

    #[Gedmo\Locale]
    protected ?string $locale = null;

    public function __construct(
        string $enumCode,
        string $name,
        string $internalId,
        int $priority = 0,
        bool $default = false
    ) {
        $this->id = ExtendHelper::buildEnumOptionId($enumCode, $internalId);
        $this->enumCode = $enumCode;
        $this->name = $name;
        $this->internalId = $internalId;
        $this->priority = $priority;
        $this->default = $default;
    }

    #[\Override]
    public function getId(): string
    {
        return $this->id;
    }

    #[\Override]
    public function setPriority($priority): static
    {
        $this->priority = (int)$priority;

        return $this;
    }

    #[\Override]
    public function getPriority(): int
    {
        return $this->priority;
    }

    #[\Override]
    public function setDefault(bool $default): static
    {
        $this->default = $default;

        return $this;
    }

    #[\Override]
    public function isDefault(): bool
    {
        return $this->default;
    }

    #[\Override]
    public function getInternalId(): string
    {
        return $this->internalId;
    }

    #[\Override]
    public function getEnumCode(): string
    {
        return $this->enumCode;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Get a human-readable representation of this object.
     * This method is used for rendering on UI as well
     *
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return $this->getDefaultName();
    }
}
