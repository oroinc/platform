<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Formatter;

use Nelmio\ApiDocBundle\Formatter\AbstractFormatter;
use Oro\Bundle\ApiBundle\ApiDoc\DocumentationProviderInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocUrlGenerator;
use Oro\Bundle\ApiBundle\ApiDoc\SecurityContextInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\Config\FileLocatorInterface;
use Twig\Environment;

/**
 * Base HTML formatter that can be used for all types of REST API views.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class HtmlFormatter extends AbstractFormatter
{
    protected SecurityContextInterface $securityContext;
    protected FileLocatorInterface $fileLocator;
    protected Environment $twig;
    protected string $apiName;
    protected ?string $endpoint;
    protected bool $enableSandbox;
    protected array $requestFormats;
    protected string $requestFormatMethod;
    protected string $defaultRequestFormat;
    protected ?string $acceptType;
    protected array $bodyFormats;
    protected string $defaultBodyFormat;
    protected ?array $authentication;
    protected string $motdTemplate;
    protected bool $defaultSectionsOpened;
    protected string $rootRoute;
    protected array $views;
    protected ?DocumentationProviderInterface $documentationProvider = null;

    public function setSecurityContext(SecurityContextInterface $securityContext): void
    {
        $this->securityContext = $securityContext;
    }

    public function setFileLocator(FileLocatorInterface $fileLocator): void
    {
        $this->fileLocator = $fileLocator;
    }

    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }

    public function setApiName(string $apiName): void
    {
        $this->apiName = $apiName;
    }

    public function setEndpoint(?string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function setEnableSandbox(bool $enableSandbox): void
    {
        $this->enableSandbox = $enableSandbox;
    }

    public function setRequestFormats(array $formats): void
    {
        $this->requestFormats = $formats;
    }

    public function setRequestFormatMethod(string $method): void
    {
        $this->requestFormatMethod = $method;
    }

    public function setDefaultRequestFormat(string $format): void
    {
        $this->defaultRequestFormat = $format;
    }

    public function setAcceptType(?string $acceptType): void
    {
        $this->acceptType = $acceptType;
    }

    public function setBodyFormats(array $bodyFormats): void
    {
        $this->bodyFormats = $bodyFormats;
    }

    public function setDefaultBodyFormat(string $defaultBodyFormat): void
    {
        $this->defaultBodyFormat = $defaultBodyFormat;
    }

    public function setAuthentication(?array $authentication): void
    {
        $this->authentication = $authentication;
    }

    public function setMotdTemplate(string $motdTemplate): void
    {
        $this->motdTemplate = $motdTemplate;
    }

    public function setDefaultSectionsOpened(bool $defaultSectionsOpened): void
    {
        $this->defaultSectionsOpened = $defaultSectionsOpened;
    }

    public function setRootRoute(string $rootRoute): void
    {
        $this->rootRoute = $rootRoute;
    }

    public function setViews(array $views): void
    {
        $this->views = $views;
    }

    public function setDocumentationProvider(DocumentationProviderInterface $documentationProvider): void
    {
        $this->documentationProvider = $documentationProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function renderOne(array $data)
    {
        return $this->twig->render('@NelmioApiDoc/resource.html.twig', array_merge(
            [
                'data'           => $data,
                'displayContent' => true,
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritDoc}
     */
    protected function render(array $collection)
    {
        return $this->twig->render('@NelmioApiDoc/resources.html.twig', array_merge(
            [
                'resources' => $collection,
            ],
            $this->getGlobalVars()
        ));
    }

    protected function getGlobalVars(): array
    {
        return [
            'apiName'                 => $this->apiName,
            'authentication'          => $this->authentication,
            'endpoint'                => $this->endpoint,
            'enableSandbox'           => $this->enableSandbox,
            'requestFormatMethod'     => $this->requestFormatMethod,
            'acceptType'              => $this->acceptType,
            'bodyFormats'             => $this->bodyFormats,
            'defaultBodyFormat'       => $this->defaultBodyFormat,
            'requestFormats'          => $this->requestFormats,
            'defaultRequestFormat'    => $this->defaultRequestFormat,
            'date'                    => date(DATE_RFC822),
            'css'                     => $this->getCss(),
            'js'                      => $this->getJs(),
            'motdTemplate'            => $this->motdTemplate,
            'defaultSectionsOpened'   => $this->defaultSectionsOpened,
            'rootRoute'               => $this->rootRoute ?? RestDocUrlGenerator::ROUTE,
            'views'                   => $this->getViews(),
            'defaultView'             => $this->getDefaultView(),
            'hasSecurityToken'        => $this->securityContext->hasSecurityToken(),
            'organizations'           => $this->securityContext->getOrganizations(),
            'organization'            => $this->securityContext->getOrganization(),
            'userName'                => $this->securityContext->getUserName(),
            'apiKey'                  => $this->securityContext->getApiKey(),
            'apiKeyGenerationHint'    => $this->securityContext->getApiKeyGenerationHint(),
            'csrfCookieName'          => $this->securityContext->getCsrfCookieName(),
            'switchOrganizationRoute' => $this->securityContext->getSwitchOrganizationRoute(),
            'loginRoute'              => $this->securityContext->getLoginRoute(),
            'logoutRoute'             => $this->securityContext->getLogoutRoute()
        ];
    }

    protected function getCss(): string
    {
        return $this->getFileContent('@NelmioApiDocBundle/Resources/public/css/screen.css');
    }

    protected function getJs(): string
    {
        $result = $this->getFileContent('@NelmioApiDocBundle/Resources/public/js/all.js');
        $result .= "\n" . $this->getFileContent('@OroApiBundle/Resources/public/lib/jquery.bind-first-0.2.3.min.js');
        if ($this->enableSandbox) {
            $result .= "\n" . $this->getFileContent('@OroApiBundle/Resources/public/lib/wsse.js');
        }

        return $result;
    }

    protected function getFileContent(string $path): string
    {
        return file_get_contents($this->fileLocator->locate($path));
    }

    protected function getViews(): array
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

    protected function getDefaultView(): string
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
