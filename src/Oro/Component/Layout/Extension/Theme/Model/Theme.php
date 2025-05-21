<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Stores all configuration values which may be configured in `theme.yml`.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Theme
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $parentTheme;

    /** @var string */
    protected $label;

    /** @var string */
    protected $icon;

    /** @var string */
    protected $faviconsPath;

    /** @var array */
    protected $imagePlaceholders = [];

    /** @var bool */
    protected $rtlSupport = false;

    /** @var bool|null */
    protected $svgIconsSupport = null;

    /** @var string */
    protected $logo;

    /** @var string */
    protected $logoSmall;

    /** @var string */
    protected $screenshot;

    /** @var string */
    protected $description;

    /** @var string */
    protected $directory;

    /** @var string[] */
    protected $groups = [];

    /** @var array */
    protected $config = [];

    /** @var ArrayCollection|PageTemplate[] */
    protected $pageTemplates;

    /** @var array */
    protected $pageTemplateTitles = [];

    protected array $fonts = [];

    /**
     * @param string $name
     * @param        $parentTheme
     */
    public function __construct($name, $parentTheme = null)
    {
        $this->name        = $name;
        $this->parentTheme = $parentTheme;
        $this->pageTemplates = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getParentTheme()
    {
        return $this->parentTheme;
    }

    /**
     * @param string $parentTheme
     */
    public function setParentTheme($parentTheme)
    {
        $this->parentTheme = $parentTheme;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getFaviconsPath()
    {
        return $this->faviconsPath;
    }

    /**
     * @param string $faviconsPath
     */
    public function setFaviconsPath($faviconsPath)
    {
        $this->faviconsPath = $faviconsPath;
    }

    public function getImagePlaceholders(): array
    {
        return $this->imagePlaceholders;
    }

    public function setImagePlaceholders(array $imagePlaceholders): void
    {
        $this->imagePlaceholders = $imagePlaceholders;
    }

    public function isRtlSupport(): bool
    {
        return $this->rtlSupport;
    }

    public function setRtlSupport(bool $rtlSupport): void
    {
        $this->rtlSupport = $rtlSupport;
    }

    public function isSvgIconsSupport(): ?bool
    {
        return $this->svgIconsSupport;
    }

    public function setSvgIconsSupport(?bool $svgIconsSupport): void
    {
        $this->svgIconsSupport = $svgIconsSupport;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    public function getLogoSmall(): ?string
    {
        return $this->logoSmall;
    }

    public function setLogoSmall(string $logoSmall): void
    {
        $this->logoSmall = $logoSmall;
    }

    /**
     * @return string
     */
    public function getScreenshot()
    {
        return $this->screenshot;
    }

    /**
     * @param string $screenshot
     */
    public function setScreenshot($screenshot)
    {
        $this->screenshot = $screenshot;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return null === $this->directory ? $this->name : $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return string[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function hasGroup($value)
    {
        return in_array($value, $this->groups, true);
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setConfigByKey($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public function getConfigByKey($key, $default = null)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }
        return $default;
    }

    /**
     * @param PageTemplate $pageTemplate
     * @param bool         $force
     * @return $this
     */
    public function addPageTemplate(PageTemplate $pageTemplate, $force = false)
    {
        $key = $pageTemplate->getKey() . '_' . $pageTemplate->getRouteName();
        if ($force === true || !$this->pageTemplates->containsKey($key)) {
            $this->pageTemplates->set($key, $pageTemplate);
        } else {
            /** @var PageTemplate $existing */
            $existing = $this->pageTemplates->get($key);
            $existing->mergeParent($pageTemplate);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|PageTemplate[]
     */
    public function getPageTemplates()
    {
        return $this->pageTemplates;
    }

    /**
     * @param string $key
     * @param string $routeName
     * @return PageTemplate
     */
    public function getPageTemplate($key, $routeName)
    {
        return $this->getPageTemplates()->filter(function (PageTemplate $pageTemplate) use ($key, $routeName) {
            return $pageTemplate->getKey() === $key
                && $pageTemplate->getRouteName() === $routeName
                && $pageTemplate->isEnabled() !== false;
        })->first();
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addPageTemplateTitle($key, $value)
    {
        $this->pageTemplateTitles[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getPageTemplateTitle($key)
    {
        return array_key_exists($key, $this->pageTemplateTitles) ? $this->pageTemplateTitles[$key] : null;
    }

    /**
     * @return array
     */
    public function getPageTemplateTitles()
    {
        return $this->pageTemplateTitles;
    }

    public function getFonts(): array
    {
        return $this->fonts;
    }

    public function setFonts(array $fonts): self
    {
        $this->fonts = $fonts;

        return $this;
    }
}
