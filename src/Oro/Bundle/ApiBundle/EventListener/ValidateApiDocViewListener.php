<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Nelmio\ApiDocBundle\Controller\ApiDocController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Checks whether the requested ApiDoc view is valid for REST API sandbox.
 * This implemented as a listener by several reasons:
 * * to avoid overriding of "NelmioApiDocBundle:layout.html.twig"
 *   template that uses "nelmio_api_doc_index" directly
 * * to allow introduce new controllers for REST API sandbox with different views
 */
class ValidateApiDocViewListener
{
    /** @var string[] */
    private $views;

    /**
     * @param string[] $views
     */
    public function __construct(array $views)
    {
        $this->views = $views;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (\is_array($controller)
            && $controller[0] instanceof ApiDocController
            && 'indexAction' === $controller[1]
            && !$this->isValidView($event->getRequest())
        ) {
            throw new NotFoundHttpException(sprintf(
                'Invalid ApiDoc view "%s".',
                $this->getView($event->getRequest())
            ));
        }
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidView(Request $request): bool
    {
        $view = $this->getView($request);

        return !$view || \in_array($view, $this->views, true);
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    protected function getView(Request $request): ?string
    {
        return $request->attributes->get('view');
    }
}
