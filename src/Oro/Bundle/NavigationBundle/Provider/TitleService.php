<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;

use Oro\Component\DependencyInjection\ServiceLink;

class TitleService implements TitleServiceInterface
{
    /**
     * Title template
     *
     * @var string
     */
    private $template;

    /**
     * Short title template
     *
     * @var string
     */
    private $shortTemplate;

    /**
     * @var TitleReaderRegistry
     */
    private $titleReaderRegistry;

    /**
     * Current title template params
     *
     * @var array
     */
    private $params = [];

    /**
     * Current title suffix
     *
     * @var array
     */
    private $suffix;

    /**
     * Current title prefix
     *
     * @var array
     */
    private $prefix;

    /**
     * @var TitleTranslator
     */
    private $titleTranslator;

    /**
     * @var ServiceLink
     */
    protected $breadcrumbManagerLink;

    /**
     * @var ConfigManager
     */
    protected $userConfigManager;

    /**
     * @param TitleReaderRegistry $titleReaderRegistry
     * @param TitleTranslator     $titleTranslator
     * @param ConfigManager       $userConfigManager
     * @param ServiceLink         $breadcrumbManagerLink
     */
    public function __construct(
        TitleReaderRegistry $titleReaderRegistry,
        TitleTranslator $titleTranslator,
        ConfigManager $userConfigManager,
        ServiceLink $breadcrumbManagerLink
    ) {
        $this->titleReaderRegistry = $titleReaderRegistry;
        $this->titleTranslator = $titleTranslator;
        $this->userConfigManager = $userConfigManager;
        $this->breadcrumbManagerLink = $breadcrumbManagerLink;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function render(
        $params = [],
        $title = null,
        $prefix = null,
        $suffix = null,
        $isJSON = false,
        $isShort = false
    ) {
        if (null !== $title && $isJSON) {
            try {
                $data = $this->jsonDecode($title);
                $params = $data['params'];
                if ($isShort) {
                    $title = $data['short_template'];
                } else {
                    $title = $data['template'];
                    if (array_key_exists('prefix', $data)) {
                        $prefix = $data['prefix'];
                    }
                    if (array_key_exists('suffix', $data)) {
                        $suffix = $data['suffix'];
                    }
                }
            } catch (\RuntimeException $e) {
                // wrong json string - ignore title
                $params = [];
                $title  = 'Untitled';
                $prefix = '';
                $suffix = '';
            }
        }
        if (empty($params)) {
            $params = $this->getParams();
        }
        if ($isShort) {
            if (null === $title) {
                $title = $this->getShortTemplate();
            }
        } else {
            if (null === $title) {
                $title = $this->getTemplate();
            }
            if (null === $prefix) {
                $prefix = $this->prefix;
            }
            if (null === $suffix) {
                $suffix = $this->suffix;
            }
            $title = $prefix . $title . $suffix;
        }

        return $this->titleTranslator->trans($title, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function setData(array $values)
    {
        if (isset($values['titleTemplate'])
            && ($this->getTemplate() == null
                || (isset($values['force']) && $values['force']))
        ) {
            $this->setTemplate($values['titleTemplate']);
        }
        if (isset($values['titleShortTemplate'])) {
            $this->setShortTemplate($values['titleShortTemplate']);
        }
        if (isset($values['params'])) {
            $this->setParams($values['params']);
        }
        if (isset($values['prefix'])) {
            $this->setPrefix($values['prefix']);
        }
        if (isset($values['suffix'])) {
            $this->setSuffix($values['suffix']);
        }

        return $this;
    }

    /**
     * Set string suffix
     *
     * @param string $suffix
     * @return $this
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Set string prefix
     *
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Set template string
     *
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template string
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set short template string
     *
     * @param string $shortTemplate
     * @return $this
     */
    public function setShortTemplate($shortTemplate)
    {
        $this->shortTemplate = $shortTemplate;

        return $this;
    }

    /**
     * Get short template string
     *
     * @return string
     */
    public function getShortTemplate()
    {
        return $this->shortTemplate;
    }

    /**
     * Return params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Setter for params
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByRoute($route, $menuName = null)
    {
        $title = $this->titleReaderRegistry->getTitleByRoute($route);

        $this->setTemplate($this->createTitle($route, $title, $menuName));
        $this->setShortTemplate($this->getShortTitle($route, $title, $menuName));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createTitle($route, $title, $menuName = null)
    {
        $titleData = [];

        if ($title) {
            $titleData[] = $title;
        }

        $breadcrumbLabels = $this->getBreadcrumbs($route, $menuName);
        if (count($breadcrumbLabels)) {
            $titleData = array_merge($titleData, $breadcrumbLabels);
        }

        $globalTitleSuffix = $this->userConfigManager->get('oro_navigation.title_suffix');
        if ($globalTitleSuffix) {
            $titleData[] = $globalTitleSuffix;
        }

        return implode(' ' . $this->userConfigManager->get('oro_navigation.title_delimiter') . ' ', $titleData);
    }

    /**
     * @param string      $route
     * @param string|null $menuName
     *
     * @return array
     */
    protected function getBreadcrumbs($route, $menuName = null)
    {
        if (!$menuName) {
            $menuName = $this->userConfigManager->get('oro_navigation.breadcrumb_menu');
        }

        /** @var BreadcrumbManagerInterface $breadcrumbManager */
        $breadcrumbManager = $this->breadcrumbManagerLink->getService();
        return $breadcrumbManager->getBreadcrumbLabels($menuName, $route);
    }

    /**
     * Get short title
     *
     * @param string      $route
     * @param string      $title
     * @param string|null $menuName
     *
     * @return string
     */
    private function getShortTitle($route, $title, $menuName = null)
    {
        if (!$title) {
            $breadcrumbs = $this->getBreadcrumbs($route, $menuName);
            if (count($breadcrumbs)) {
                $title = $breadcrumbs[0];
            }
        }

        return $title;
    }

    /**
     * {@inheritdoc}
     */
    public function getSerialized()
    {
        $data = [
            'template'       => $this->getTemplate(),
            'short_template' => $this->getShortTemplate(),
            'params'         => $this->getParams()
        ];
        if ($this->prefix) {
            $data['prefix'] = $this->prefix;
        }
        if ($this->suffix) {
            $data['suffix'] = $this->suffix;
        }

        return $this->jsonEncode($data);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function jsonEncode(array $data)
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(sprintf(
                'The title serialization failed. Error: %s. Message: %s.',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $encoded;
    }

    /**
     * @param string $encoded
     *
     * @return array
     */
    protected function jsonDecode($encoded)
    {
        $data = json_decode($encoded, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(sprintf(
                'The title deserialization failed. Error: %s. Message: %s.',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $data;
    }
}
