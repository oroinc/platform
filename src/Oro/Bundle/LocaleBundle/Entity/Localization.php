<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroLocaleBundle_Entity_Localization;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectType;
use Oro\Bundle\TranslationBundle\Entity\Language;

/**
 * Localization entity class.
 *
 * @method LocalizedFallbackValue getTitle(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultTitle()
 * @method LocalizedFallbackValue setDefaultTitle($string)
 * @mixin OroLocaleBundle_Entity_Localization
 */
#[ORM\Entity(repositoryClass: LocalizationRepository::class)]
#[ORM\Table(name: 'oro_localization')]
#[Config(
    routeName: 'oro_locale_localization_index',
    routeView: 'oro_locale_localization_view',
    routeUpdate: 'oro_locale_localization_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-list'],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'form' => ['form_type' => LocalizationSelectType::class, 'grid_name' => 'oro-locale-localizations-select-grid']
    ]
)]
class Localization implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    const DEFAULT_LOCALIZATION = 'default';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $name = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_localization_title')]
    #[ORM\JoinColumn(name: 'localization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    protected ?Collection $titles = null;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    protected ?Language $language = null;

    #[ORM\Column(name: 'formatting_code', type: Types::STRING, length: 16, nullable: false)]
    protected ?string $formattingCode = null;

    #[ORM\Column(name: 'rtl_mode', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $rtlMode = false;

    #[ORM\ManyToOne(targetEntity: Localization::class, inversedBy: 'childLocalizations')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Localization $parentLocalization = null;

    /**
     * @var Collection<int, Localization>
     */
    #[ORM\OneToMany(mappedBy: 'parentLocalization', targetEntity: Localization::class)]
    protected ?Collection $childLocalizations = null;

    public function __construct()
    {
        $this->childLocalizations = new ArrayCollection();
        $this->titles = new ArrayCollection();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Language $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->language ? $this->language->getCode() : null;
    }

    /**
     * @param string $formattingCode
     *
     * @return $this
     */
    public function setFormattingCode($formattingCode)
    {
        $this->formattingCode = $formattingCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormattingCode()
    {
        return $this->formattingCode;
    }

    public function isRtlMode(): bool
    {
        return $this->rtlMode;
    }

    /**
     * @param bool $rtlMode
     * @return $this
     */
    public function setRtlMode(bool $rtlMode): self
    {
        $this->rtlMode = $rtlMode;

        return $this;
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
     * @param Localization|null $parentLocalization
     *
     * @return $this
     */
    public function setParentLocalization(Localization $parentLocalization = null)
    {
        $this->parentLocalization = $parentLocalization;

        return $this;
    }

    /**
     * @return Localization|null
     */
    public function getParentLocalization()
    {
        return $this->parentLocalization;
    }

    /**
     * @return Collection|Localization[]
     */
    public function getChildLocalizations()
    {
        return $this->childLocalizations;
    }

    /**
     * @param Localization $childLocalization
     * @return $this
     */
    public function addChildLocalization(Localization $childLocalization)
    {
        if (!$this->childLocalizations->contains($childLocalization)) {
            $this->childLocalizations->add($childLocalization);
        }

        return $this;
    }

    /**
     * @param Localization $childLocalization
     * @return $this
     */
    public function removeChildLocalization(Localization $childLocalization)
    {
        if ($this->childLocalizations->contains($childLocalization)) {
            $this->childLocalizations->removeElement($childLocalization);
            $childLocalization->setParentLocalization(null);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function addTitle(LocalizedFallbackValue $title)
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return $this
     */
    public function removeTitle(LocalizedFallbackValue $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getHierarchy()
    {
        return $this->getLocaleHierarchy($this);
    }

    /**
     * @param Localization $localization
     * @return array
     */
    protected function getLocaleHierarchy(Localization $localization)
    {
        $localeHierarchy = [];

        $parent = $localization->getParentLocalization();
        if ($parent) {
            $localeHierarchy[] = $parent->getId();
            $localeHierarchy = array_merge($localeHierarchy, $this->getLocaleHierarchy($parent));
        } else {
            // For default value without locale
            $localeHierarchy = [null];
        }

        return $localeHierarchy;
    }

    /**
     * @param bool $includeOwnId
     * @return array
     */
    public function getChildrenIds($includeOwnId = false)
    {
        $ids = $this->processChildrenIds($this);
        if ($includeOwnId && $this->getId()) {
            $ids[] = $this->getId();
        }

        $ids = array_unique($ids);

        sort($ids);

        return $ids;
    }

    /**
     * @param Localization $localization
     * @return array
     */
    protected function processChildrenIds(Localization $localization)
    {
        $ids = [];
        foreach ($localization->getChildLocalizations() as $child) {
            foreach ($this->processChildrenIds($child) as $id) {
                $ids[] = $id;
            }

            $ids[] = $child->getId();
        }

        return $ids;
    }
}
