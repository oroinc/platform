<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Bundle\FrameworkBundle\Client as BaseClient;
use Symfony\Component\BrowserKit\Request as InternalRequest;
use Symfony\Component\BrowserKit\Response as InternalResponse;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Exception\UserInputErrorExceptionInterface;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;

class Client extends BaseClient
{
    const LOCAL_URL = 'http://localhost';

    /**
     * @var PDOConnection
     */
    protected $pdoConnection;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var boolean
     */
    protected $hasPerformedRequest;

    /**
     * @var boolean[]
     */
    protected $loadedFixtures;

    /**
     * @var bool
     */
    protected $isHashNavigation = false;

    /**
     * {@inheritdoc}
     */
    public function request(
        $method,
        $uri,
        array $parameters = array(),
        array $files = array(),
        array $server = array(),
        $content = null,
        $changeHistory = true
    ) {
        if (strpos($uri, 'http://') === false) {
            $uri = self::LOCAL_URL . $uri;
        }

        if ($this->getServerParameter('HTTP_X-WSSE', '') !== '' && !isset($server['HTTP_X-WSSE'])) {
            //generate new WSSE header
            parent::setServerParameters(WebTestCase::generateWsseAuthHeader());
        }

        $hashNavigationHeader = $this->getHashNavigationHeader();
        if ($this->isHashNavigationRequest($uri, $parameters, $server)) {
            $server[$hashNavigationHeader] = 1;
        }

        parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        if ($this->isHashNavigationResponse($this->response, $server)) {
            /** @var InternalRequest $internalRequest */
            $internalRequest = $this->internalRequest;
            /** @var Response $response */
            $response = $this->response;

            $content = json_decode($response->getContent(), true);

            if ($this->isRedirectResponse($content)) {
                $this->redirect = $content['location'];
                // force regular redirect
                if (!empty($content['fullRedirect'])) {
                    $this->internalRequest = new InternalRequest(
                        $internalRequest->getUri(),
                        $internalRequest->getMethod(),
                        $internalRequest->getParameters(),
                        $internalRequest->getFiles(),
                        $internalRequest->getCookies(),
                        array_merge($internalRequest->getServer(), [$hashNavigationHeader => 0]),
                        $internalRequest->getContent()
                    );
                }
                $response->setContent('');
                $response->setStatusCode(302);
                /** @var InternalResponse $internalResponse */
                $internalResponse = $this->internalResponse;
                $this->internalResponse = new InternalResponse('', 302, $internalResponse->getHeaders());
                if ($this->followRedirects && $this->redirect) {
                    return $this->crawler = $this->followRedirect();
                }
            }

            if ($this->isContentResponse($content)) {
                $response->setContent($this->buildHtml($content));
                $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                $this->crawler = $this->createCrawlerFromContent(
                    $internalRequest->getUri(),
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
     * @return Response
     */
    public function requestGrid($gridParameters, $filter = array(), $isRealRequest = false)
    {
        list($gridName, $gridParameters) = $this->parseGridParameters($gridParameters, $filter);

        if ($isRealRequest) {
            $this->request(
                'GET',
                $this->getUrl('oro_datagrid_index', $gridParameters)
            );

            return $this->getResponse();
        } else {
            $container = $this->getContainer();

            $request = Request::create($this->getUrl('oro_datagrid_index', $gridParameters));
            $container->get('oro_datagrid.datagrid.request_parameters_factory')->setRequest($request);
            /** @var Manager $gridManager */
            $gridManager = $container->get('oro_datagrid.datagrid.manager');
            $gridConfig  = $gridManager->getConfigurationForGrid($gridName);
            $acl         = $gridConfig->getAclResource();

            if ($acl && !$container->get('oro_security.security_facade')->isGranted($acl)) {
                return new Response('Access denied.', 403);
            }

            $grid = $gridManager->getDatagridByRequestParams($gridName);

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
    protected function getUrl($name, $parameters = array(), $absolute = false)
    {
        return $this->getContainer()->get('router')->generate($name, $parameters, $absolute);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRequest($request)
    {
        if ($this->hasPerformedRequest) {
            $this->kernel->shutdown();
            $this->kernel->boot();
        } else {
            $this->hasPerformedRequest = true;
        }

        $response = $this->kernel->handle($request);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
        return $response;
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

        return sprintf($html, $title, $title, $flashMessages, $content['beforeContentAddition'], $content['content']);
    }

    /**
     * @param array $server
     */
    public function mergeServerParameters(array $server)
    {
        $this->server = array_replace($this->server, $server);
    }
}
