<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField; // required by DatesAwareTrait

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_locale_set")
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
class LocaleSet implements DatesAwareInterface
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
     * @var LocaleSet
     *
     * @ORM\ManyToOne(targetEntity="LocaleSet", inversedBy="childLocaleSets")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $parentLocaleSet;

    /**
     * @var Collection|LocaleSet[]
     *
     * @ORM\OneToMany(targetEntity="LocaleSet", mappedBy="parentLocaleSet")
     */
    protected $childLocaleSets;

    public function __construct()
    {
        $this->childLocaleSets = new ArrayCollection();
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
     * @param LocaleSet $parentLocaleSet
     *
     * @return $this
     */
    public function setParentLocaleSet(LocaleSet $parentLocaleSet = null)
    {
        $this->parentLocaleSet = $parentLocaleSet;

        return $this;
    }

    /**
     * @return LocaleSet
     */
    public function getParentLocaleSet()
    {
        return $this->parentLocaleSet;
    }

    /**
     * @param Collection|LocaleSet[] $childLocaleSets
     *
     * @return $this
     */
    public function setChildLocaleSets($childLocaleSets)
    {
        $this->childLocaleSets = $childLocaleSets;

        return $this;
    }

    /**
     * @return Collection|LocaleSet[]
     */
    public function getChildLocaleSets()
    {
        return $this->childLocaleSets;
    }

    /**
     * @param LocaleSet $localeSet
     * @return $this
     */
    public function addChildLocaleSet(LocaleSet $localeSet)
    {
        if (!$this->hasChildLocaleSet($localeSet)) {
            $this->childLocaleSets->add($localeSet);
        }

        return $this;

    }

    /**
     * @param LocaleSet $localeSet
     * @return $this
     */
    public function removeChildLocaleSet(LocaleSet $localeSet)
    {
        if ($this->hasChildLocaleSet($localeSet)) {
            $this->childLocaleSets->removeElement($localeSet);
            $localeSet->setParentLocaleSet(null);
        }

        return $this;
    }

    /**
     * @param LocaleSet $localeSet
     * @return boolean
     */
    public function hasChildLocaleSet(LocaleSet $localeSet)
    {
        return $this->childLocaleSets->contains($localeSet);
    }
}
