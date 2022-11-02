<?php
namespace Oro\Bundle\NavigationBundle\Event;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

/**
 * Updates response headers and check if full redirect is required when using hash navigation.
 */
class ResponseHashnavListener
{
    const HASH_NAVIGATION_HEADER = 'x-oro-hash-navigation';

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var bool
     */
    protected $isDebug;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param Environment           $twig
     * @param bool                  $isDebug
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        Environment $twig,
        $isDebug = false
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->isDebug = $isDebug;
    }

    /**
     * Checking request and response and decide whether we need a redirect
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onResponse(ResponseEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();
        if ($request->get(self::HASH_NAVIGATION_HEADER) || $request->headers->get(self::HASH_NAVIGATION_HEADER)) {
            $location       = '';
            $isFullRedirect = false;
            if ($response->isRedirect()) {
                $location = $response->headers->get('location');
                if ($request->attributes->get('_fullRedirect') || !is_object($this->tokenStorage->getToken())) {
                    $isFullRedirect = true;
                }
            }
            if ($response->isNotFound() || ($response->getStatusCode() == 503 && !$this->isDebug)) {
                $location = $request->getUri();
                $isFullRedirect = true;
            }
            if ($location) {
                $response->headers->remove('location');
                $response->setStatusCode(200);

                $template = $this->twig->render(
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
}
