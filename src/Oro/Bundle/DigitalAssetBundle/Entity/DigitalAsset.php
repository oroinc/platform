<?php

namespace Oro\Bundle\DigitalAssetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * DigitalAsset entity class. Represents a file asset which can be used as upload file for other entities.
 *
 * @ORM\Table(
 *     name="oro_digital_asset",
 *     indexes={
 *          @ORM\Index(name="created_at_idx", columns={"created_at"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_digital_asset_index",
 *      routeView="oro_digital_asset_view",
 *      routeUpdate="oro_digital_asset_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-file"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL"
 *          },
 *          "dataaudit"={
 *              "auditable"=false,
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          },
 *          "comment"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "workflow"={
 *              "show_step_in_grid"=false
 *          },
 *          "tag"={
 *              "immutable"=true
 *          }
 *      }
 * )
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method Collection getChildFiles()
 */
class DigitalAsset implements DatesAwareInterface, OrganizationAwareInterface, ExtendEntityInterface
{
    use UserAwareTrait;
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_digital_asset_title",
     *      joinColumns={
     *          @ORM\JoinColumn(name="digital_asset_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $titles;

    /**
     * @var File|null
     *
     * @ORM\OneToOne(
     *     targetEntity="Oro\Bundle\AttachmentBundle\Entity\File",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     *  )
     * @ORM\JoinColumn(name="source_file_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     *
     * @ConfigField(
     *      defaultValues={
     *          "attachment"={
     *              "acl_protected"=true,
     *              "file_applications"={"default"}
     *          }
     *      }
     * )
     */
    protected $sourceFile;

    public function __construct()
    {
        $this->titles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    public function addTitle(LocalizedFallbackValue $title): self
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    public function removeTitle(LocalizedFallbackValue $title): self
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    public function setSourceFile(File $file): self
    {
        $this->sourceFile = $file;

        return $this;
    }

    public function getSourceFile(): ?File
    {
        return $this->sourceFile;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __clone()
    {
        throw new \BadMethodCallException('Not supported');
    }
}
