<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * Updates response headers and check if full redirect is required when using hash navigation.
 */
class ResponseHashnavListener implements ServiceSubscriberInterface
{
    public const HASH_NAVIGATION_HEADER = 'x-oro-hash-navigation';

    private TokenStorageInterface $tokenStorage;
    private bool $debug;
    private ContainerInterface $container;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        bool $debug,
        ContainerInterface $container
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->debug = $debug;
        $this->container = $container;
    }

    /**
     * Checking request and response and decide whether we need a redirect
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->get(self::HASH_NAVIGATION_HEADER) || $request->headers->get(self::HASH_NAVIGATION_HEADER)) {
            $location = '';
            $isFullRedirect = false;
            $response = $event->getResponse();
            if ($response->isRedirect()) {
                $location = $response->headers->get('location');
                if ($request->attributes->get('_fullRedirect') || !\is_object($this->tokenStorage->getToken())) {
                    $isFullRedirect = true;
                }
            }
            if ($response->isNotFound()
                || ($response->getStatusCode() === Response::HTTP_SERVICE_UNAVAILABLE && !$this->debug)
            ) {
                $location = $request->getUri();
                $isFullRedirect = true;
            }
            if ($location) {
                $response->headers->remove('location');
                $response->setStatusCode(Response::HTTP_OK);

                $template = $this->getTwig()->render(
                    '@OroNavigation/HashNav/redirect.html.twig',
                    [
                        'full_redirect' => $isFullRedirect,
                        'location'      => $location,
                    ]
                );

                $response->setContent($template);
            }

            // disable cache for ajax navigation pages and change content type to json
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->addCacheControlDirective('no-cache', true);
            $response->headers->addCacheControlDirective('max-age', 0);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);
            $event->setResponse($response);
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        /**
         * Inject TWIG service via the service locator because it is optional and not all requests use it,
         * e.g. REST API and AJAX requests do not need TWIG. This solution improves performance of such requests.
         */
        return [
            Environment::class
        ];
    }

    private function getTwig(): Environment
    {
        return $this->container->get(Environment::class);
    }
}
