<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Exception\UserInputErrorExceptionInterface;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Symfony\Bundle\FrameworkBundle\KernelBrowser as BaseKernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\Request as InternalRequest;
use Symfony\Component\BrowserKit\Response as InternalResponse;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Simulates a browser and makes requests to a Kernel object.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Client extends BaseKernelBrowser
{
    public const LOCAL_HOST = 'localhost';
    public const LOCAL_URL = 'http://' . self::LOCAL_HOST;

    /**
     * @var bool
     */
    protected $isHashNavigation = false;

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function request(
        string $method,
        string $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        string $content = null,
        bool $changeHistory = true
    ): Crawler {
        if (!str_starts_with($uri, 'http://') && !str_starts_with($uri, 'https://')) {
            $uri = self::LOCAL_URL . $uri;
        }

        if (!isset($server['HTTP_X-WSSE']) && $this->getServerParameter('HTTP_X-WSSE', '') !== '') {
            // generate new WSSE header
            $this->mergeServerParameters(WebTestCase::generateWsseAuthHeader());
        }

        $hashNavigationHeader = $this->getHashNavigationHeader();
        if ($this->isHashNavigationRequest($uri, $parameters, $server)) {
            $server[$hashNavigationHeader] = 1;
        }

        $this->setSessionCookie($server);

        if (($content === null || $content === '') && $parameters && \in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $this->setServerParameter('CONTENT_TYPE', 'application/json');
            $this->setServerParameter('HTTP_ACCEPT', 'application/json');
            try {
                parent::request(
                    $method,
                    $uri,
                    [],
                    $files,
                    $server,
                    json_encode($parameters, JSON_THROW_ON_ERROR),
                    $changeHistory
                );
            } finally {
                unset($this->server['CONTENT_TYPE'], $this->server['HTTP_ACCEPT']);
            }
        } else {
            parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
        }

        if ($this->isHashNavigationResponse($this->response, $server)) {
            /** @var Response $response */
            $response = $this->response;

            $content = json_decode($response->getContent(), true);

            if ($this->isRedirectResponse($content)) {
                $this->redirect = $content['location'];
                // force regular redirect
                if (!empty($content['fullRedirect'])) {
                    $this->internalRequest = new InternalRequest(
                        $this->internalRequest->getUri(),
                        $this->internalRequest->getMethod(),
                        $this->internalRequest->getParameters(),
                        $this->internalRequest->getFiles(),
                        $this->internalRequest->getCookies(),
                        array_merge($this->internalRequest->getServer(), [$hashNavigationHeader => 0]),
                        $this->internalRequest->getContent()
                    );
                }
                $response->setContent('');
                $response->setStatusCode(302);
                $this->internalResponse = new InternalResponse('', 302, $this->internalResponse->getHeaders());
                if ($this->followRedirects && $this->redirect) {
                    return $this->crawler = $this->followRedirect();
                }
            }

            if ($this->isContentResponse($content)) {
                $response->setContent($this->buildHtml($content));
                $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                $this->crawler = $this->createCrawlerFromContent(
                    $this->internalRequest->getUri(),
                    $response->getContent(),
                    'text/html'
                );
            }
        }

        return $this->crawler;
    }

    /**
     * @param array|string $gridParameters
     * @param array $filter
     * @param bool $isRealRequest
     * @param string $route
     * @return Response
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function requestGrid(
        $gridParameters,
        $filter = array(),
        $isRealRequest = false,
        $route = 'oro_datagrid_index'
    ) {
        [$gridName, $gridParameters] = $this->parseGridParameters($gridParameters, $filter);

        if ($isRealRequest) {
            $this->request(
                'GET',
                $this->getUrl($route, $gridParameters)
            );

            return $this->getResponse();
        } else {
            $container = $this->getContainer();

            $request = Request::create($this->getUrl($route, $gridParameters));
            $requestStack = $container->get('request_stack');
            // store previously added requests to restore after grid request
            $originalStack = [];
            while ($requestStack->getMainRequest()) {
                $originalStack[] = $requestStack->pop();
            }
            $originalStack = array_reverse($originalStack);
            $container->get('request_stack')->push($request);

            $session = $container->has('session')
                ? $container->get('session')
                : $container->get('session.factory')->createSession();

            // if new session was created, and we have a cookie with session-id, set it back
            if (!$session->getId()) {
                $sessCookie = $this->getCookieJar()->get('MOCKSESSID');
                if ($sessCookie && $sessCookie->getValue()) {
                    $session->setId($sessCookie->getValue());
                }
            }

            $request->setSession($session);
            // set 'default' theme, as storefront grids require it
            if ($route === 'oro_frontend_datagrid_index') {
                $request->attributes->set('_theme', 'default');
            }

            /** @var ManagerInterface $gridManager */
            $gridManager = $container->get('oro_datagrid.datagrid.manager');
            $gridConfig  = $gridManager->getConfigurationForGrid($gridName);
            $acl         = $gridConfig->getAclResource();

            if ($acl && !$container->get('security.authorization_checker')->isGranted($acl)) {
                return new Response('Access denied.', 403);
            }

            $grid = $gridManager->getDatagridByRequestParams($gridName);

            // put back origin request objects to not affect original test logic
            foreach ($originalStack as $requests) {
                $requestStack->push($requests);
            }

            try {
                $result = $grid->getData();
                return new JsonResponse($result->toArray());
            } catch (\Exception $e) {
                if ($e instanceof UserInputErrorExceptionInterface) {
                    return new JsonResponse(
                        [
                            'type'    => UserInputErrorExceptionInterface::TYPE,
                            'message' =>
                                $container->get('translator')->trans($e->getMessageTemplate(), $e->getMessageParams())
                        ],
                        500
                    );
                } else {
                    return new Response($e->getMessage(), 500);
                }
            }
        }
    }

    public function requestFrontendGrid(
        $gridParameters,
        $filter = array(),
        $isRealRequest = false,
    ): JsonResponse|Response {
        return $this->requestGrid($gridParameters, $filter, $isRealRequest, 'oro_frontend_datagrid_index');
    }

    #[\Override]
    protected function filterRequest(InternalRequest $request): Request
    {
        $httpRequest = parent::filterRequest($request);
        if (str_starts_with($httpRequest->headers->get('CONTENT_TYPE', ''), 'application/x-www-form-urlencoded')
            && \is_string($httpRequest->getContent())
            && \in_array(
                strtoupper($httpRequest->server->get('REQUEST_METHOD', 'GET')),
                ['POST', 'PUT', 'DELETE', 'PATCH']
            )
        ) {
            parse_str($httpRequest->getContent(), $data);
            $httpRequest->request = new InputBag($data);
        }

        return $httpRequest;
    }

    /**
     * @param array|string $gridParameters
     * @param array $filter
     * @return array ['<gridNameString>', <gridParametersArray>]
     */
    protected function parseGridParameters($gridParameters, $filter = array())
    {
        if (is_string($gridParameters)) {
            $gridName = $gridParameters;
            $gridParameters = ['gridName' => $gridName];
        } else {
            $gridName = $gridParameters['gridName'];
        }

        //transform parameters to nested array
        $parameters = [];
        foreach ($filter as $param => $value) {
            $param .= '=' . $value;
            parse_str($param, $output);
            $parameters = array_merge_recursive($parameters, $output);
        }

        return [$gridName, array_merge_recursive($gridParameters, $parameters)];
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    protected function getUrl(string $name, array $parameters = [], bool $absolute = false)
    {
        return $this->getContainer()
            ->get('router')
            ->generate(
                $name,
                $parameters,
                $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
            );
    }

    /**
     * @param bool $flag
     */
    public function useHashNavigation($flag)
    {
        $this->isHashNavigation = $flag;
    }

    /**
     * @param null|array $content
     * @return bool
     */
    protected function isRedirectResponse($content)
    {
        return $content && !empty($content['redirect']);
    }

    /**
     * @param null|array $content
     * @return bool
     */
    protected function isContentResponse($content)
    {
        return $content && is_array($content) && array_key_exists('content', $content);
    }

    /**
     * @return string
     */
    protected function getHashNavigationHeader()
    {
        return 'HTTP_' . strtoupper(ResponseHashnavListener::HASH_NAVIGATION_HEADER);
    }

    /**
     * @param $uri
     * @param array $parameters
     * @param array $server
     * @return bool
     */
    protected function isHashNavigationRequest($uri, array $parameters, array $server)
    {
        $isWidget = !empty($parameters['_widgetContainer']) || strpos($uri, '_widgetContainer=') !== false;

        return $this->isHashNavigation
            && !$isWidget
            && !array_key_exists($this->getHashNavigationHeader(), $server);
    }

    /**
     * @param object|Response $response
     * @param array $server
     * @return bool
     */
    protected function isHashNavigationResponse($response, array $server)
    {
        if (!$this->isHashNavigation || empty($server[$this->getHashNavigationHeader()])) {
            return false;
        }

        return $response instanceof Response &&
            $response->getStatusCode() === 200 &&
            $response->headers->get('Content-Type') === 'application/json';
    }

    /**
     * @param array $content
     * @return string
     */
    protected function buildHtml(array $content)
    {
        $title = !empty($content['title']) ? $content['title'] : '';

        $flashMessages = '';
        if (!empty($content['flashMessages'])) {
            foreach ($content['flashMessages'] as $type => $messages) {
                foreach ($messages as $message) {
                    $flashMessages .= sprintf('<div class="%s">%s</div>', $type, $message);
                }
            }
        }

        $html =
            '<html>
                <head>
                    <title>%s</title>
                    <script id="page-title" type="text/html">%s</script>
                </head>
                <body>%s%s%s</body>
            </html>';

        return sprintf(
            $html,
            $title,
            $title,
            $flashMessages,
            $content['beforeContentAddition'] ?? '',
            $content['content'] ?? ''
        );
    }

    public function mergeServerParameters(array $server)
    {
        $this->server = array_replace($this->server, $server);
    }

    /**
     * @return string|null
     */
    private function getSessionName()
    {
        $container = $this->getContainer();

        try {
            $session = $container->get('request_stack')->getSession();
        } catch (SessionNotFoundException $exception) {
            $session = $container->has('session') ? $container->get('session') : null;
        }

        return $session?->getName();
    }

    private function setSessionCookie(array &$server)
    {
        if (array_key_exists('HTTP_X-WSSE', $server)) {
            return;
        }

        if (array_key_exists('HTTP_SESSION', $server)) {
            if ($server['HTTP_SESSION']) {
                $sessionName = $this->getSessionName();
                if ($sessionName && null === $this->getCookieJar()->get($sessionName)) {
                    $this->getCookieJar()->set(new Cookie($sessionName, $server['HTTP_SESSION']));
                }
            }
            unset($server['HTTP_SESSION']);
        }
    }
}
