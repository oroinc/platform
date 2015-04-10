<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Component\HttpFoundation\Request;

use Twig_Environment;

class WidgetExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_widget';

    /**
     * Protect extension from infinite loop
     *
     * @var bool
     */
    protected $rendered = [];

    /**
     * @var Request
     */
    protected $request;

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            'oro_widget_render' => new \Twig_Function_Method(
                $this,
                'render',
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true
                ]
            )
        ];
    }

    /**
     * Renders a widget.
     *
     * @param \Twig_Environment $environment
     * @param array             $options
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function render(Twig_Environment $environment, array $options = [])
    {
        $optionsHash = md5(json_encode($options));

        if (!empty($this->rendered[$optionsHash])) {
            return '';
        }

        $this->rendered[$optionsHash] = true;

        if (!array_key_exists('url', $options)) {
            throw new \InvalidArgumentException('Option url is required');
        }

        if (!array_key_exists('widgetType', $options)) {
            throw new \InvalidArgumentException('Option widgetType is required');
        } else {
            $widgetType = $options['widgetType'];
            unset($options['widgetType']);
        }

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
            $options['wid']
        );

        if ($this->request) {
            $options['url'] = $this->addRequestParameters($options['url']);
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
     * @param string $url
     * @param string $widgetType
     * @param string $wid
     * @return string
     */
    protected function getUrlWithContainer($url, $widgetType, $wid)
    {
        if (strpos($url, '_widgetContainer=') === false) {
            $parts      = parse_url($url);
            $widgetPart = '_widgetContainer=' . $widgetType . '&_wid=' . $wid;
            if (array_key_exists('query', $parts)) {
                $separator = $parts['query'] ? '&' : '';
                $newQuery  = $parts['query'] . $separator . $widgetPart;
                $url       = str_replace($parts['query'], $newQuery, $url);
            } else {
                $url .= '?' . $widgetPart;
            }
        }

        return $url;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function addRequestParameters($url)
    {
        $urlParts = parse_url($url);

        $urlPath   = !empty($urlParts['path']) ? $urlParts['path'] : '';
        $urlParams = [];
        if (!empty($urlParts['query'])) {
            parse_str($urlParts['query'], $urlParams);
        }

        $requestParams = $this->request->query->all();

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
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
