<?php

namespace Oro\Bundle\ThemeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\AuditableBusinessUnitAwareTrait;
use Oro\Bundle\ThemeBundle\Entity\Enum\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Entity\Repository\ThemeConfigurationRepository;

/**
 * ThemeConfiguration entity class
 */
#[Config(
    routeName: "oro_theme_configuration_index",
    routeView: "oro_theme_configuration_view",
    routeCreate: "oro_theme_configuration_create",
    routeUpdate: "oro_theme_configuration_update",
    defaultValues: [
      "entity" => [
         "icon" => "fa-briefcase"
      ],
      "ownership" => [
         "owner_type" => "BUSINESS_UNIT",
         "owner_field_name" => "owner",
         "owner_column_name" => "business_unit_owner_id",
         "organization_field_name" => "organization",
         "organization_column_name" => "organization_id"
      ],
      "form" => [
          "form_type" => "Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType",
          "grid_name" => "oro-theme-configuration-grid",
      ],
      "dataaudit" => [
          "auditable" => true
      ],
      "security" => [
          "type" => "ACL",
          "group_name" => ""
      ]
    ]
)]
#[ORM\Entity(repositoryClass: ThemeConfigurationRepository::class)]
#[ORM\Table(name: 'oro_theme_configuration')]
#[ORM\HasLifecycleCallbacks]
class ThemeConfiguration implements DatesAwareInterface, OrganizationAwareInterface
{
    use DatesAwareTrait;
    use AuditableBusinessUnitAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        enumType: ThemeConfigurationType::class,
        options: ["default" => "Storefront"]
    )]
    protected ThemeConfigurationType $type = ThemeConfigurationType::Storefront;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    protected ?string $theme = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    protected array $configuration = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ThemeConfigurationType
    {
        return $this->type;
    }

    public function setType(ThemeConfigurationType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getConfigurationOption(string $key): mixed
    {
        return $this->configuration[$key] ?? null;
    }

    public function addConfigurationOption(string $key, mixed $value): self
    {
        $this->configuration[$key] = $value;

        return $this;
    }

    public function removeConfigurationOption(string $key): self
    {
        unset($this->configuration[$key]);

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public static function getTypes(): array
    {
        return ThemeConfigurationType::cases();
    }
}
