<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\ContentProvider\TwigContentProviderManager;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UIBundle\Event\Events;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions for miscellaneous HTML processing tasks:
 *   - oro_ui_scroll_data_before
 *   - render_block
 *   - oro_widget_render
 *   - oro_form_process
 *   - oro_view_process
 *   - oro_get_content
 *   - isMobileVersion
 *   - isDesktopVersion
 *   - oro_url_add_query
 *   - oro_is_url_local
 *   - skype_button
 *   - oro_form_additional_data (Returns Additional section data which is used for rendering)
 *
 * Provides Twig filters that expose some common PHP functions:
 *   - oro_js_template_content
 *   - merge_recursive
 *   - uniqid
 *   - floor
 *   - ceil
 *   - oro_preg_replace
 *   - oro_sort_by
 *   - url_decode
 *   - array_unique
 *
 * Provides a Twig tag to work with placeholders:
 *   - placeholder
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UiExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    // Big number is set to guarantee Additional section is rendered at the end
    public const ADDITIONAL_SECTION_PRIORITY = 10000;

    // Represents key which is used for Additional section in array of blocks to identify it and make possible to work
    public const ADDITIONAL_SECTION_KEY = 'oro_additional_section_key';

    private const SKYPE_BUTTON_TEMPLATE = '@OroUI/skype_button.html.twig';

    protected ContainerInterface $container;
    /** Protect extension from infinite loop during a widget rendering */
    private array $renderedWidgets = [];
    private ?bool $isMobile = null;
    private ?bool $isDesktop = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            new TwigFilter('oro_js_template_content', [$this, 'prepareJsTemplateContent']),
            new TwigFilter('merge_recursive', [ArrayUtil::class, 'arrayMergeRecursiveDistinct']),
            new TwigFilter('uniqid', 'uniqid'),
            new TwigFilter('floor', 'floor'),
            new TwigFilter('ceil', 'ceil'),
            new TwigFilter('render_content', static function ($string) {
                return $string;
            }),
            new TwigFilter('oro_preg_replace', [$this, 'pregReplace']),
            new TwigFilter('oro_sort_by', [$this, 'sortBy']),
            new TwigFilter('url_decode', 'urldecode'),
            new TwigFilter('url_add_query_parameters', [$this, 'urlAddQueryParameters']),
            new TwigFilter('array_unique', 'array_unique'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_ui_scroll_data_before',
                [$this, 'scrollDataBefore'],
                ['needs_environment' => true]
            ),
            new TwigFunction(
                'render_block',
                [$this, 'renderBlock'],
                ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'oro_widget_render',
                [$this, 'renderWidget'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'oro_form_process',
                [$this, 'processForm'],
                ['needs_environment' => true]
            ),
            new TwigFunction(
                'oro_form_additional_data',
                [$this, 'renderAdditionalData'],
                ['needs_environment' => true]
            ),
            new TwigFunction(
                'oro_view_process',
                [$this, 'processView'],
                ['needs_environment' => true]
            ),
            new TwigFunction(
                'oro_get_content',
                [$this, 'getContent'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction('isMobileVersion', [$this, 'isMobile']),
            new TwigFunction('isDesktopVersion', [$this, 'isDesktop']),
            new TwigFunction('oro_url_add_query', [$this, 'addUrlQuery']),
            new TwigFunction('oro_is_url_local', [$this, 'isUrlLocal']),
            new TwigFunction(
                'skype_button',
                [$this, 'getSkypeButton'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction('oro_default_page', [$this, 'getDefaultPage']),
        ];
    }

    /**
     * @param TwigEnvironment   $environment
     * @param string            $pageIdentifier
     * @param array             $data
     * @param object            $entity
     * @param FormView|null     $formView
     * @return array
     */
    public function scrollDataBefore(
        TwigEnvironment $environment,
        $pageIdentifier,
        array $data,
        $entity,
        FormView $formView = null
    ) {
        $event = new BeforeListRenderEvent($environment, new ScrollData($data), $entity, $formView);
        $this->getEventDispatcher()->dispatch($event, 'oro_ui.scroll_data.before.' . $pageIdentifier);

        return $event->getScrollData()->getData();
    }

    /**
     * @param TwigEnvironment $env
     * @param array $context
     * @param string $template
     * @param string $block
     * @param array $extraContext
     *
     * @return string
     * @throws \Throwable
     */
    public function renderBlock(TwigEnvironment $env, $context, $template, $block, $extraContext = []): string
    {
        $templateWrapper = $env->load($template);

        return $templateWrapper->renderBlock($block, array_merge($context, $extraContext));
    }

    /**
     * @param TwigEnvironment   $environment
     * @param array             $data
     * @param FormView          $form
     * @param object|null       $entity
     *
     * @return array
     */
    public function processForm(TwigEnvironment $environment, array $data, FormView $form, $entity = null)
    {
        $event = new BeforeFormRenderEvent($form, $data, $environment, $entity);
        $this->getEventDispatcher()->dispatch($event, Events::BEFORE_UPDATE_FORM_RENDER);

        return $event->getFormData();
    }

    public function renderAdditionalData(
        TwigEnvironment $environment,
        FormView $form,
        string $label,
        array $additionalData = []
    ): array {
        foreach ($form->children as $child) {
            if (empty($child->vars['extra_field']) || $child->isRendered()) {
                continue;
            }

            $additionalData[$child->vars['name']] = $environment->render(
                '@OroUI/form_row.html.twig',
                ['child' => $child]
            );
        }

        if ($additionalData) {
            $additionalData = [
                self::ADDITIONAL_SECTION_KEY =>
                    [
                        'title' => $label,
                        'priority' => self::ADDITIONAL_SECTION_PRIORITY,
                        'subblocks' => [
                            [
                                'title' => '',
                                'useSpan' => false,
                                'data' => $additionalData
                            ]
                        ]
                    ]
            ];
        }

        return $additionalData;
    }

    /**
     * @param TwigEnvironment   $environment
     * @param array             $data
     * @param object            $entity
     *
     * @return array
     */
    public function processView(TwigEnvironment $environment, array $data, $entity)
    {
        $event = new BeforeViewRenderEvent($environment, $data, $entity);
        $this->getEventDispatcher()->dispatch($event, Events::BEFORE_VIEW_RENDER);

        return $event->getData();
    }

    /**
     * @param TwigEnvironment   $environment
     * @param array             $options
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function renderWidget(TwigEnvironment $environment, array $options = [])
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

        if (!\array_key_exists('elementFirst', $options)) {
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
            $options['widgetTemplate'] ?? null
        );

        $request = $this->getRequest();
        if (null !== $request) {
            $options['url'] = $this->addRequestParameters($request, $options['url']);
        }

        return $environment->render(
            '@OroUI/widget_loader.html.twig',
            [
                'elementId'  => $elementId,
                'options'    => $options,
                'widgetType' => $widgetType,
            ]
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function validateOptions(array $options)
    {
        if (!\array_key_exists('url', $options)) {
            throw new \InvalidArgumentException('Option url is required');
        }

        if (!\array_key_exists('widgetType', $options)) {
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
        if (!str_contains($url, '_widgetContainer=')) {
            $parts = parse_url($url);
            $widgetPart = '_widgetContainer=' . $widgetType . '&_wid=' . $wid;
            if ($widgetTemplate && $widgetTemplate !== $widgetType) {
                $widgetPart .= '&_widgetContainerTemplate=' . $widgetTemplate;
            }
            if (\array_key_exists('query', $parts)) {
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
        $content = $this->getTwigContentProviderManager()->getContent($keys);
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
        if (\is_string($subject) && !empty($subject)) {
            $subject = preg_replace($pattern, $replacement, $subject, $limit);
        }

        return $subject;
    }

    public function urlAddQueryParameters(string $url, array $parameters): string
    {
        $urlParts = parse_url($url);
        $queryParameters = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParameters);
        }

        $queryParameters = ArrayUtil::arrayMergeRecursiveDistinct($queryParameters, $parameters);
        $urlParts['query'] = http_build_query($queryParameters);

        return sprintf(
            '%s%s%s%s%s',
            isset($urlParts['scheme'])? $urlParts['scheme'] . '://' : '',
            $urlParts['host'] ?? '',
            isset($urlParts['port']) ? ':' . $urlParts['port'] : '',
            $urlParts['path'] ?? '',
            $urlParts['query'] ? '?' . $urlParts['query']: ''
        );
    }

    /**
     * Check by user-agent if request was from mobile device.
     */
    public function isMobile(): bool
    {
        if (null === $this->isMobile) {
            $this->isMobile = $this->getUserAgent()->isMobile();
        }

        return $this->isMobile;
    }

    /**
     * Check by user-agent if request was not from mobile device.
     */
    public function isDesktop(): bool
    {
        if (null === $this->isDesktop) {
            $this->isDesktop = $this->getUserAgent()->isDesktop();
        }

        return $this->isDesktop;
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
        $sortingType = $options['sorting-type'] ?? 'number';
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
            $options['reverse'] ?? false,
            $options['property'] ?? 'priority',
            $sortingFlags
        );

        return $array;
    }

    /**
     * Skype.UI wrapper
     *
     * @param TwigEnvironment   $environment
     * @param string            $skypeUserName
     * @param array             $options
     *
     * @return int
     */
    public function getSkypeButton(TwigEnvironment $environment, $skypeUserName, $options = [])
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

        $templateName = $options['template'] ?? self::SKYPE_BUTTON_TEMPLATE;
        unset($options['template']);

        return $environment->render($templateName, ['options' => $options]);
    }

    public function getDefaultPage(): string
    {
        return $this->getRouter()->generate('oro_default');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_ui.content_provider.manager.twig' => TwigContentProviderManager::class,
            'oro_ui.user_agent_provider' => UserAgentProviderInterface::class,
            EventDispatcherInterface::class,
            RouterInterface::class,
            RequestStack::class,
        ];
    }

    protected function getTwigContentProviderManager(): TwigContentProviderManager
    {
        return $this->container->get('oro_ui.content_provider.manager.twig');
    }

    protected function getUserAgent(): UserAgent
    {
        /** @var UserAgentProviderInterface $userAgentProvider */
        $userAgentProvider = $this->container->get('oro_ui.user_agent_provider');

        return $userAgentProvider->getUserAgent();
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(EventDispatcherInterface::class);
    }

    protected function getRouter(): RouterInterface
    {
        return $this->container->get(RouterInterface::class);
    }

    protected function getRequest(): ?Request
    {
        return $this->container->get(RequestStack::class)->getCurrentRequest();
    }
}
