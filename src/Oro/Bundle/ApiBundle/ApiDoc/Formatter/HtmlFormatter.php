<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Formatter;

use Nelmio\ApiDocBundle\Formatter\AbstractFormatter;
use Oro\Bundle\ApiBundle\ApiDoc\DocumentationProviderInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocUrlGenerator;
use Oro\Bundle\ApiBundle\ApiDoc\SecurityContextInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Base HTML formatter that can be used for all types of REST API views.
 */
class HtmlFormatter extends AbstractFormatter
{
    /** @var SecurityContextInterface */
    protected $securityContext;

    /** @var FileLocatorInterface */
    protected $fileLocator;

    /** @var EngineInterface */
    protected $engine;

    /** @var string */
    protected $apiName;

    /** @var string */
    protected $endpoint;

    /** @var bool */
    protected $enableSandbox;

    /** @var array */
    protected $requestFormats;

    /** @var string */
    protected $requestFormatMethod;

    /** @var string */
    protected $defaultRequestFormat;

    /** @var string */
    protected $acceptType;

    /** @var array */
    protected $bodyFormats;

    /** @var string */
    protected $defaultBodyFormat;

    /** @var array */
    protected $authentication;

    /** @var string */
    protected $motdTemplate;

    /** @var bool */
    protected $defaultSectionsOpened;

    /** @var string */
    protected $rootRoute;

    /** @var array */
    protected $views;

    /** @var DocumentationProviderInterface|null */
    protected $documentationProvider;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function setSecurityContext(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @param FileLocatorInterface $fileLocator
     */
    public function setFileLocator(FileLocatorInterface $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param EngineInterface $engine
     */
    public function setTemplatingEngine(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @param string $apiName
     */
    public function setApiName($apiName)
    {
        $this->apiName = $apiName;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param bool $enableSandbox
     */
    public function setEnableSandbox($enableSandbox)
    {
        $this->enableSandbox = $enableSandbox;
    }

    /**
     * @param array $formats
     */
    public function setRequestFormats(array $formats)
    {
        $this->requestFormats = $formats;
    }

    /**
     * @param string $method
     */
    public function setRequestFormatMethod($method)
    {
        $this->requestFormatMethod = $method;
    }

    /**
     * @param string $format
     */
    public function setDefaultRequestFormat($format)
    {
        $this->defaultRequestFormat = $format;
    }

    /**
     * @param string $acceptType
     */
    public function setAcceptType($acceptType)
    {
        $this->acceptType = $acceptType;
    }

    /**
     * @param array $bodyFormats
     */
    public function setBodyFormats(array $bodyFormats)
    {
        $this->bodyFormats = $bodyFormats;
    }

    /**
     * @param string $defaultBodyFormat
     */
    public function setDefaultBodyFormat($defaultBodyFormat)
    {
        $this->defaultBodyFormat = $defaultBodyFormat;
    }

    /**
     * @param array $authentication
     */
    public function setAuthentication(array $authentication = null)
    {
        $this->authentication = $authentication;
    }

    /**
     * @param string $motdTemplate
     */
    public function setMotdTemplate($motdTemplate)
    {
        $this->motdTemplate = $motdTemplate;
    }

    /**
     * @param bool $defaultSectionsOpened
     */
    public function setDefaultSectionsOpened($defaultSectionsOpened)
    {
        $this->defaultSectionsOpened = $defaultSectionsOpened;
    }

    /**
     * @param string $rootRoute
     */
    public function setRootRoute($rootRoute)
    {
        $this->rootRoute = $rootRoute;
    }

    /**
     * @param array $views
     */
    public function setViews(array $views)
    {
        $this->views = $views;
    }

    /**
     * @param DocumentationProviderInterface $documentationProvider
     */
    public function setDocumentationProvider(DocumentationProviderInterface $documentationProvider)
    {
        $this->documentationProvider = $documentationProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function renderOne(array $data)
    {
        return $this->engine->render('NelmioApiDocBundle::resource.html.twig', array_merge(
            [
                'data'           => $data,
                'displayContent' => true,
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function render(array $collection)
    {
        return $this->engine->render('NelmioApiDocBundle::resources.html.twig', array_merge(
            [
                'resources' => $collection,
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * @return array
     */
    protected function getGlobalVars()
    {
        return [
            'apiName'               => $this->apiName,
            'authentication'        => $this->authentication,
            'endpoint'              => $this->endpoint,
            'enableSandbox'         => $this->enableSandbox,
            'requestFormatMethod'   => $this->requestFormatMethod,
            'acceptType'            => $this->acceptType,
            'bodyFormats'           => $this->bodyFormats,
            'defaultBodyFormat'     => $this->defaultBodyFormat,
            'requestFormats'        => $this->requestFormats,
            'defaultRequestFormat'  => $this->defaultRequestFormat,
            'date'                  => date(DATE_RFC822),
            'css'                   => $this->getCss(),
            'js'                    => $this->getJs(),
            'motdTemplate'          => $this->motdTemplate,
            'defaultSectionsOpened' => $this->defaultSectionsOpened,
            'rootRoute'             => $this->rootRoute ?? RestDocUrlGenerator::ROUTE,
            'views'                 => $this->getViews(),
            'defaultView'           => $this->getDefaultView(),
            'hasSecurityToken'      => $this->securityContext->hasSecurityToken(),
            'userName'              => $this->securityContext->getUserName(),
            'apiKey'                => $this->securityContext->getApiKey(),
            'apiKeyGenerationHint'  => $this->securityContext->getApiKeyGenerationHint(),
            'loginRoute'            => $this->securityContext->getLoginRoute(),
            'logoutRoute'           => $this->securityContext->getLogoutRoute()
        ];
    }

    /**
     * @return string
     */
    protected function getCss()
    {
        return $this->getFileContent('@NelmioApiDocBundle/Resources/public/css/screen.css');
    }

    /**
     * @return string
     */
    protected function getJs()
    {
        $result = $this->getFileContent('@NelmioApiDocBundle/Resources/public/js/all.js');
        $result .= "\n" . $this->getFileContent('@OroApiBundle/Resources/public/lib/jquery.bind-first-0.2.3.min.js');
        if ($this->enableSandbox) {
            $result .= "\n" . $this->getFileContent('@OroApiBundle/Resources/public/lib/wsse.js');
        }

        return $result;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getFileContent($path)
    {
        return file_get_contents($this->fileLocator->locate($path));
    }

    /**
     * @return array
     */
    protected function getViews()
    {
        if (null === $this->documentationProvider) {
            return $this->views;
        }

        $views = [];
        foreach ($this->views as $key => $view) {
            if (!empty($view['request_type'])) {
                $documentation = $this->documentationProvider->getDocumentation(
                    new RequestType($view['request_type'])
                );
                if ($documentation) {
                    $view['documentation'] = $documentation;
                }
            }
            $views[$key] = $view;
        }

        return $views;
    }

    /**
     * @return string
     */
    protected function getDefaultView()
    {
        $result = '';
        foreach ($this->views as $name => $view) {
            if (isset($view['default']) && $view['default']) {
                $result = $name;
                break;
            }
        }

        return $result;
    }
}
