<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Redirects to the current API view after a user is logged out.
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /** @var LogoutSuccessHandlerInterface */
    private $innerHandler;

    /** @var RestDocUrlGeneratorInterface */
    private $restDocUrlGenerator;

    /**
     * @param LogoutSuccessHandlerInterface $innerHandler
     * @param RestDocUrlGeneratorInterface  $restDocUrlGenerator
     */
    public function __construct(
        LogoutSuccessHandlerInterface $innerHandler,
        RestDocUrlGeneratorInterface $restDocUrlGenerator
    ) {
        $this->innerHandler = $innerHandler;
        $this->restDocUrlGenerator = $restDocUrlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request)
    {
        $view = $request->query->get('_api_view');
        if ($view) {
            return new RedirectResponse($this->restDocUrlGenerator->generate($view));
        }

        return $this->innerHandler->onLogoutSuccess($request);
    }
}
