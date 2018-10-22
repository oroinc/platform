<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UIBundle\Event\Events;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

/**
 * The extension adds:
 *  - common php functions as twig filters (uniqid, ceil, floor)
 *  - isMobileVersion and isDesktopVersion functions
 *  - series of processors for HTML (oro_form_process, oro_widget_render and other)
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class UiExtension extends \Twig_Extension
{
    const SKYPE_BUTTON_TEMPLATE = 'OroUIBundle::skype_button.html.twig';

    /** @var ContainerInterface */
    protected $container;

    /**
     * Protect extension from infinite loop during a widget rendering
     *
     * @var bool
     */
    protected $renderedWidgets = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * @return Request|null
     */
    protected function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @return ContentProviderManager
     */
    protected function getContentProviderManager()
    {
        return $this->container->get('oro_ui.content_provider.manager');
    }

    /**
     * @return UserAgentProviderInterface
     */
    protected function getUserAgentProvider()
    {
        return $this->container->get('oro_ui.user_agent_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ui';
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return [
            new PlaceholderTokenParser()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_js_template_content', [$this, 'prepareJsTemplateContent']),
            new \Twig_SimpleFilter(
                'merge_recursive',
                ['Oro\Component\PhpUtils\ArrayUtil', 'arrayMergeRecursiveDistinct']
            ),
            new \Twig_SimpleFilter('uniqid', 'uniqid'),
            new \Twig_SimpleFilter('floor', 'floor'),
            new \Twig_SimpleFilter('ceil', 'ceil'),
            new \Twig_SimpleFilter('oro_preg_replace', [$this, 'pregReplace']),
            new \Twig_SimpleFilter('oro_sort_by', [$this, 'sortBy'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_ui_scroll_data_before',
                [$this, 'scrollDataBefore'],
                ['needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'render_block',
                [$this, 'renderBlock'],
                ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_widget_render',
                [$this, 'renderWidget'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_form_process',
                [$this, 'processForm'],
                ['needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'oro_view_process',
                [$this, 'processView'],
                ['needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'oro_get_content',
                [$this, 'getContent'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction('isMobileVersion', [$this, 'isMobile']),
            new \Twig_SimpleFunction('isDesktopVersion', [$this, 'isDesktop']),
            new \Twig_SimpleFunction('oro_url_add_query', [$this, 'addUrlQuery']),
            new \Twig_SimpleFunction('oro_is_url_local', [$this, 'isUrlLocal']),
            new \Twig_SimpleFunction(
                'skype_button',
                [$this, 'getSkypeButton'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param string            $pageIdentifier
     * @param array             $data
     * @param object            $entity
     * @param FormView          $formView
     * @return array
     */
    public function scrollDataBefore(
        \Twig_Environment $environment,
        $pageIdentifier,
        array $data,
        $entity,
        FormView $formView = null
    ) {
        $event = new BeforeListRenderEvent($environment, new ScrollData($data), $entity, $formView);
        $this->getEventDispatcher()->dispatch('oro_ui.scroll_data.before.' . $pageIdentifier, $event);

        return $event->getScrollData()->getData();
    }

    /**
     * @param \Twig_Environment $env
     * @param array             $context
     * @param string            $template
     * @param string            $block
     * @param array             $extraContext
     *
     * @return string
     */
    public function renderBlock(\Twig_Environment $env, $context, $template, $block, $extraContext = [])
    {
        /** @var \Twig_Template $template */
        $template = $env->loadTemplate($template);

        return $template->renderBlock($block, array_merge($context, $extraContext));
    }

    /**
     * @param \Twig_Environment $environment
     * @param array             $data
     * @param FormView          $form
     * @param object|null       $entity
     *
     * @return array
     */
    public function processForm(\Twig_Environment $environment, array $data, FormView $form, $entity = null)
    {
        $event = new BeforeFormRenderEvent($form, $data, $environment, $entity);
        $this->getEventDispatcher()->dispatch(Events::BEFORE_UPDATE_FORM_RENDER, $event);

        return $event->getFormData();
    }

    /**
     * @param \Twig_Environment $environment
     * @param array             $data
     * @param object            $entity
     *
     * @return array
     */
    public function processView(\Twig_Environment $environment, array $data, $entity)
    {
        $event = new BeforeViewRenderEvent($environment, $data, $entity);
        $this->getEventDispatcher()->dispatch(Events::BEFORE_VIEW_RENDER, $event);

        return $event->getData();
    }

    /**
     * @param \Twig_Environment $environment
     * @param array             $options
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function renderWidget(\Twig_Environment $environment, array $options = [])
    {
        $optionsHash = md5(json_encode($options));

        if (!empty($this->renderedWidgets[$optionsHash])) {
            return '';
        }

        $this->renderedWidgets[$optionsHash] = true;

        $this->validateOptions($options);

        $widgetType = $options['widgetType'];
        unset($options['widgetType']);

        if (!isset($options['wid'])) {
            $options['wid'] = $this->getUniqueIdentifier();
        }

        $elementId = 'widget-container-' . $options['wid'];

        if (!array_key_exists('elementFirst', $options)) {
            $options['elementFirst'] = true;
        }

        if ($options['elementFirst']) {
            $options['el'] = '#' . $elementId . ' .widget-content:first';
        } else {
            $options['container'] = '#' . $elementId;
        }

        $options['url'] = $this->getUrlWithContainer(
            $options['url'],
            $widgetType,
            $options['wid'],
            isset($options['widgetTemplate']) ? $options['widgetTemplate'] : null
        );

        $request = $this->getRequest();
        if (null !== $request) {
            $options['url'] = $this->addRequestParameters($request, $options['url']);
        }

        return $environment->render(
            'OroUIBundle::widget_loader.html.twig',
            [
                'elementId'  => $elementId,
                'options'    => $options,
                'widgetType' => $widgetType,
            ]
        );
    }

    /**
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    protected function validateOptions(array $options)
    {
        if (!array_key_exists('url', $options)) {
            throw new \InvalidArgumentException('Option url is required');
        }

        if (!array_key_exists('widgetType', $options)) {
            throw new \InvalidArgumentException('Option widgetType is required');
        }
    }

    /**
     * @param string $url
     * @param string $widgetType
     * @param string $wid
     * @param string|null $widgetTemplate
     *
     * @return string
     */
    protected function getUrlWithContainer($url, $widgetType, $wid, $widgetTemplate = null)
    {
        if (strpos($url, '_widgetContainer=') === false) {
            $parts = parse_url($url);
            $widgetPart = '_widgetContainer=' . $widgetType . '&_wid=' . $wid;
            if ($widgetTemplate && $widgetTemplate !== $widgetType) {
                $widgetPart .= '&_widgetContainerTemplate=' . $widgetTemplate;
            }
            if (array_key_exists('query', $parts)) {
                $separator = $parts['query'] ? '&' : '';
                $newQuery = $parts['query'] . $separator . $widgetPart;
                $url = str_replace($parts['query'], $newQuery, $url);
            } else {
                $url .= '?' . $widgetPart;
            }
        }

        return $url;
    }

    /**
     * @param Request $request
     * @param string  $url
     *
     * @return string
     */
    protected function addRequestParameters(Request $request, $url)
    {
        $urlParts = parse_url($url);

        $urlPath = !empty($urlParts['path']) ? $urlParts['path'] : '';
        $urlParams = [];
        if (!empty($urlParts['query'])) {
            parse_str($urlParts['query'], $urlParams);
        }

        $requestParams = $request->query->all();

        $mergedParams = array_merge($requestParams, $urlParams);
        if (empty($mergedParams)) {
            return $urlPath;
        }

        return $urlPath . '?' . http_build_query($mergedParams);
    }

    /**
     * @return string
     */
    protected function getUniqueIdentifier()
    {
        return str_replace('.', '-', uniqid('', true));
    }

    /**
     * @param array $additionalContent
     * @param array $keys
     *
     * @return array
     */
    public function getContent(array $additionalContent = null, array $keys = null)
    {
        $content = $this->getContentProviderManager()->getContent($keys);
        if ($additionalContent) {
            $content = array_merge($content, $additionalContent);
        }
        if ($keys) {
            $content = array_intersect_key($content, array_combine($keys, $keys));
        }

        return $content;
    }

    /**
     * Prepares the given string to use inside JavaScript template.
     * Example:
     * <script type="text/html" id="my_template">
     *     content|oro_js_template_content|raw
     * </script>
     *
     * @param string $content
     *
     * @return string
     */
    public function prepareJsTemplateContent($content)
    {
        if (!$content) {
            return $content;
        }

        $result = '';
        $offset = 0;
        while (false !== $start = strpos($content, '<script', $offset)) {
            if (false !== $end = strpos($content, '</script>', $start + 7)) {
                $result .= substr($content, $offset, $start - $offset);
                $result .= '<% print("<sc" + "ript") %>';
                $result .= strtr(
                    substr($content, $start + 7, $end - $start - 7),
                    [
                        '<%' => '<% print("<" + "%") %>',
                        '%>' => '<% print("%" + ">") %>'
                    ]
                );
                $result .= '<% print("</sc" + "ript>") %>';
                $offset = $end + 9;
            }
        }
        $result .= substr($content, $offset);

        return $result;
    }

    /**
     * @param string $subject
     * @param string $pattern
     * @param string $replacement
     * @param int    $limit
     *
     * @return mixed
     */
    public function pregReplace($subject, $pattern, $replacement, $limit = -1)
    {
        if (is_string($subject) && !empty($subject)) {
            $subject = preg_replace($pattern, $replacement, $subject, $limit);
        }

        return $subject;
    }

    /**
     * Check by user-agent if request was from mobile device
     *
     * @return bool
     */
    public function isMobile()
    {
        return $this->getUserAgentProvider()->getUserAgent()->isMobile();
    }


    /**
     * Check by user-agent if request was not from mobile device
     *
     * @return bool
     */
    public function isDesktop()
    {
        return $this->getUserAgentProvider()->getUserAgent()->isDesktop();
    }

    /**
     * @param string $link
     *
     * @return string
     */
    public function addUrlQuery($link)
    {
        $request = $this->getRequest();
        if (null === $request) {
            return $link;
        }

        $parts = parse_url($link);
        $urlQueryParts = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $urlQueryParts);
        }

        $requestQueryParts = $request->query->all();
        if ($requestQueryParts && $requestQueryParts != $urlQueryParts) {
            $mergedQueryParts = array_merge($requestQueryParts, $urlQueryParts);

            $basicUrlPart = $parts['path'];
            $updatedUrlPart = $parts['path'] . '?' . http_build_query($mergedQueryParts);

            if (!empty($parts['host'])) {
                $basicUrlPart = $parts['host'] . $basicUrlPart;
                $updatedUrlPart = $parts['host'] . $updatedUrlPart;
            }
            if (!empty($parts['query'])) {
                $basicUrlPart .= '?' . $parts['query'];
            }

            $link = str_replace($basicUrlPart, $updatedUrlPart, $link);
        }

        return $link;
    }

    /**
     * @param string $link
     *
     * @return bool
     */
    public function isUrlLocal($link)
    {
        $request = $this->getRequest();
        if (null === $request) {
            return false;
        }

        $parts = parse_url($link);
        $isLocal = true;

        if (!empty($parts['host']) && $parts['host'] !== $request->getHost()) {
            $isLocal = false;
        } elseif (!empty($parts['port']) && $parts['port'] !== $request->getPort()) {
            $isLocal = false;
        } elseif (!empty($parts['scheme']) && $request->isSecure() && $parts['scheme'] !== 'https') {
            // going out from secure connection to insecure page on same domain is not local
            $isLocal = false;
        }

        return $isLocal;
    }

    /**
     * Sorts an array by specified property.
     *
     * This method uses the stable sorting algorithm. See http://en.wikipedia.org/wiki/Sorting_algorithm#Stability
     *
     * Supported options:
     *  property     [string]  The path of the property by which the array should be sorted. Defaults to 'priority'
     *  reverse      [boolean] Indicates whether the sorting should be performed in reverse order. Defaults to FALSE
     *  sorting-type [string]  number, string or string-case (for case-insensitive sorting). Defaults to 'number'
     *
     * @param array $array   The array to be sorted
     * @param array $options The sorting options
     *
     * @return array The sorted array
     */
    public function sortBy(array $array, array $options = [])
    {
        $sortingType = self::getOption($options, 'sorting-type', 'number');
        if ($sortingType === 'number') {
            $sortingFlags = SORT_NUMERIC;
        } else {
            $sortingFlags = SORT_STRING;
            if ($sortingType === 'string-case') {
                $sortingFlags |= SORT_FLAG_CASE;
            }
        }

        ArrayUtil::sortBy(
            $array,
            self::getOption($options, 'reverse', false),
            self::getOption($options, 'property', 'priority'),
            $sortingFlags
        );

        return $array;
    }

    /**
     * @param array  $options
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    protected static function getOption($options, $name, $defaultValue = null)
    {
        return isset($options[$name])
            ? $options[$name]
            : $defaultValue;
    }

    /**
     * Skype.UI wrapper
     *
     * @param \Twig_Environment $environment
     * @param string            $skypeUserName
     * @param array             $options
     *
     * @return int
     */
    public function getSkypeButton(\Twig_Environment $environment, $skypeUserName, $options = [])
    {
        if (!isset($options['element'])) {
            $options['element'] = 'skype_button_' . md5($skypeUserName) . '_' . mt_rand(1, 99999);
        }
        if (!isset($options['participants'])) {
            $options['participants'] = (array)$skypeUserName;
        }
        if (!isset($options['name'])) {
            $options['name'] = 'call';
        }

        $templateName = isset($options['template']) ? $options['template'] : self::SKYPE_BUTTON_TEMPLATE;
        unset($options['template']);

        return $environment->render($templateName, ['options' => $options]);
    }
}
