<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Navigation title helper.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
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
                $this->checkRenderParams($data, $isShort);
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
     * @throws \InvalidArgumentException
     */
    public function setParams(array $params)
    {
        $this->validateParams($params);

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
     * Create title template for current route and menu name
     *
     * @param string      $route
     * @param string      $title
     * @param string|null $menuName
     *
     * @return string
     */
    public function createTitle($route, $title, $menuName = null)
    {
        $titleData = $this->mergeTitleWithBreadcrumbLabels($route, $title, $menuName);

        $globalTitleSuffix = $this->userConfigManager->get('oro_navigation.title_suffix');
        if ($globalTitleSuffix) {
            $titleData[] = $globalTitleSuffix;
        }

        return implode(' ' . $this->userConfigManager->get('oro_navigation.title_delimiter') . ' ', $titleData);
    }

    /**
     * @param array $data
     * @param bool $isShort
     *
     * @throws \RuntimeException
     */
    protected function checkRenderParams(array $data, $isShort)
    {
        if (!isset($data['params'])) {
            throw new \RuntimeException('Missing key "params" in JSON title.');
        }

        if ($isShort) {
            if (!array_key_exists('short_template', $data)) {
                throw new \RuntimeException('Missing key "short_template" in JSON title.');
            }
        } elseif (!array_key_exists('template', $data)) {
            throw new \RuntimeException('Missing key "template" in JSON title.');
        }
    }

    /**
     * @param string      $route
     * @param string|null $menuName
     *
     * @return array
     */
    protected function getBreadcrumbLabels($route, $menuName = null)
    {
        if (!$menuName) {
            $menuName = $this->userConfigManager->get('oro_navigation.breadcrumb_menu');
        }

        /** @var BreadcrumbManagerInterface $breadcrumbManager */
        $breadcrumbManager = $this->breadcrumbManagerLink->getService();
        return $breadcrumbManager->getBreadcrumbLabels($menuName, $route);
    }

    /**
     * @param string|null $menuName
     * @param bool $isInverse
     * @param string|null $route
     *
     * @return array
     */
    protected function getBreadcrumbs($menuName = null, $isInverse = true, $route = null)
    {
        if (!$menuName) {
            $menuName = $this->userConfigManager->get('oro_navigation.breadcrumb_menu');
        }

        /** @var BreadcrumbManagerInterface $breadcrumbManager */
        $breadcrumbManager = $this->breadcrumbManagerLink->getService();
        return $breadcrumbManager->getBreadcrumbs($menuName, $isInverse, $route);
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
            $breadcrumbs = $this->getBreadcrumbLabels($route, $menuName);
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
        $params = [];
        foreach ($this->getParams() as $paramName => $paramValue) {
            //Explicitly case object to string because json_encode can not serialize it correct
            $params[$paramName] = is_object($paramValue) ? (string)$paramValue : $paramValue;
        }
        $data = [
            'template'       => $this->getTemplate(),
            'short_template' => $this->getShortTemplate(),
            'params'         => $params
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

    /**
     * @param string $route
     * @param string $title
     * @param string $menuName
     *
     * @return array
     */
    protected function mergeTitleWithBreadcrumbLabels($route, $title, $menuName)
    {
        $titleData = [];
        if ($title) {
            $titleData[] = $title;
        }
        $breadcrumbLabels = $this->getBreadcrumbLabels($route, $menuName);
        if (count($breadcrumbLabels)) {
            $breadcrumbs = $this->getBreadcrumbs($menuName, false, $route);
            if (!empty($breadcrumbs)) {
                /** @var ItemInterface $menuItem */
                $menuItem = $breadcrumbs[0]['item'];
                $routes = $menuItem->getExtra('routes', []);
                if ($routes === [$route] && $title) {
                    unset($breadcrumbLabels[0]);
                }
            }

            $titleData = array_merge($titleData, $breadcrumbLabels);
        }

        return $titleData;
    }

    /**
     * @param array $params
     * @throws \InvalidArgumentException
     */
    private function validateParams(array $params)
    {
        foreach ($params as $key => $value) {
            if (is_object($value) && !method_exists($value, '__toString')) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Object of type %s used for "%s" title param don\'t have __toString() method.',
                        get_class($value),
                        $key
                    )
                );
            }
        }
    }
}
