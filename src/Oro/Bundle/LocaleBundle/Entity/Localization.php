<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_localization")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *      }
 * )
 */
class Localization implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, unique=true, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="i18n_code", type="string", length=64, nullable=false)
     */
    protected $i18nCode;

    /**
     * @var string
     *
     * @ORM\Column(name="l10n_code", type="string", length=64, nullable=false)
     */
    protected $l10nCode;

    /**
     * @var Localization
     *
     * @ORM\ManyToOne(targetEntity="Localization", inversedBy="childLocalizations")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $parentLocalization;

    /**
     * @var Collection|Localization[]
     *
     * @ORM\OneToMany(targetEntity="Localization", mappedBy="parentLocalization")
     */
    protected $childLocalizations;

    public function __construct()
    {
        $this->childLocalizations = new ArrayCollection();
    }

    /**
     * @param string $i18nCode
     *
     * @return $this
     */
    public function setI18nCode($i18nCode)
    {
        $this->i18nCode = $i18nCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getI18nCode()
    {
        return $this->i18nCode;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $l10nCode
     *
     * @return $this
     */
    public function setL10nCode($l10nCode)
    {
        $this->l10nCode = $l10nCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getL10nCode()
    {
        return $this->l10nCode;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Localization $parentLocalization
     *
     * @return $this
     */
    public function setParentLocalization(Localization $parentLocalization = null)
    {
        $this->parentLocalization = $parentLocalization;

        return $this;
    }

    /**
     * @return Localization
     */
    public function getParentLocalization()
    {
        return $this->parentLocalization;
    }

    /**
     * @param Collection|Localization[] $childLocalizations
     *
     * @return $this
     */
    public function setChildLocalizations($childLocalizations)
    {
        $this->childLocalizations = $childLocalizations;

        return $this;
    }

    /**
     * @return Collection|Localization[]
     */
    public function getChildLocalizations()
    {
        return $this->childLocalizations;
    }

    /**
     * @param Localization $localization
     * @return $this
     */
    public function addChildLocalization(Localization $localization)
    {
        if (!$this->hasChildLocalization($localization)) {
            $this->childLocalizations->add($localization);
        }

        return $this;

    }

    /**
     * @param Localization $localization
     * @return $this
     */
    public function removeChildLocalization(Localization $localization)
    {
        if ($this->hasChildLocalization($localization)) {
            $this->childLocalizations->removeElement($localization);
            $localization->setParentLocalization(null);
        }

        return $this;
    }

    /**
     * @param Localization $localization
     * @return boolean
     */
    public function hasChildLocalization(Localization $localization)
    {
        return $this->childLocalizations->contains($localization);
    }
}
