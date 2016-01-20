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
        if (!$request) {
            throw new \InvalidArgumentException('Missing $request');
        }

        $data = $request->request->all();

        if (!array_key_exists('data', $data)) {
            throw new \InvalidArgumentException('Missing data in $request');
        }

        if (!array_key_exists('url', $data['data'])) {
            throw new \InvalidArgumentException('Missing url in $data');
        }

        $cleanUrl = str_replace($request->getScriptName(), '', $data['data']['url']);
        if (!$cleanUrl) {
            throw new \InvalidArgumentException('$cleanUrl is empty');
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
        $uri = null;
        if (isset($data['cleanUrl'])) {
            if (isset($data['type'])) {
                $wid = isset($data['wid']) ? $data['wid'] : $this->getUniqueIdentifier();
                $uri = $this->getUrlWithContainer($data['cleanUrl'], $data['type'], $wid);
            } else {
                $uri = $data['cleanUrl'];
            }
        }

        if (null === $uri) {
            throw new \InvalidArgumentException('cleanUrl is missing');
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
        return str_replace('.', '-', uniqid('', true));
    }
}
