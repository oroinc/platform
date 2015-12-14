<?php

namespace Oro\Bundle\WindowsBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;

class WindowsStateRequestManager
{
    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $request = $this->requestStack->getCurrentRequest();
        $data = $request->request->all();

        if (!array_key_exists('data', $data)) {
            throw new \InvalidArgumentException();
        }

        if (!array_key_exists('url', $data['data'])) {
            throw new \InvalidArgumentException();
        }

        $cleanUrl = str_replace($request->server->get('SCRIPT_NAME'), '', $data['data']['url']);
        if (!$cleanUrl) {
            throw new \InvalidArgumentException();
        }

        $data['data']['cleanUrl'] = $cleanUrl;

        return $data['data'];
    }

    /**
     * @param array $data
     * @return string
     */
    public function getUri(array $data)
    {
        if (isset($data['cleanUrl'])) {
            if (isset($data['type'])) {
                $wid = isset($data['wid']) ? $data['wid'] : $this->getUniqueIdentifier();
                $uri = $this->getUrlWithContainer($data['cleanUrl'], $data['type'], $wid);
            } else {
                $uri = $data['cleanUrl'];
            }
        }

        if (!$uri) {
            throw new \InvalidArgumentException();
        }

        return $uri;
    }

    /**
     * @param string $url
     * @param string $container
     * @param string $wid
     *
     * @return string
     */
    protected function getUrlWithContainer($url, $container, $wid)
    {
        if (strpos($url, '_widgetContainer=') === false) {
            $parts = parse_url($url);
            $widgetPart = '_widgetContainer=' . $container . '&_wid=' . $wid;
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
     * @return string
     */
    protected function getUniqueIdentifier()
    {
        return str_replace('.', '-', uniqid('windows_state', true));
    }
}
