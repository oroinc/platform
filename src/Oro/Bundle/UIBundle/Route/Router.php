<?php

namespace Oro\Bundle\UIBundle\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class Router
{
    const ACTION_PARAMETER     = 'input_action';
    const ACTION_SAVE_AND_STAY = 'save_and_stay';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SymfonyRouter
     */
    protected $router;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    public function __construct(Request $request, SymfonyRouter $router, SecurityFacade $securityFacade)
    {
        $this->request        = $request;
        $this->router         = $router;
        $this->securityFacade = $securityFacade;
    }

    /**
     * "Save and Stay" and "Save and Close" buttons handler
     *
     * @param array $saveButtonRoute   array with router data for save and stay button
     * @param array $saveAndCloseRoute array with router data for save and close button
     * @param int   $status            redirect status
     *
     * @return RedirectResponse
     * @throws \LogicException
     * @deprecated To be removed in 1.1. Use redirectAfterSave method instead
     */
    public function actionRedirect(array $saveButtonRoute, array $saveAndCloseRoute, $status = 302)
    {
        if ($this->request->get(self::ACTION_PARAMETER) == self::ACTION_SAVE_AND_STAY) {
            $routeData = $saveButtonRoute;
        } else {
            $routeData = $saveAndCloseRoute;
        }

        if (!isset($routeData['route'])) {
            throw new \LogicException('Parameter "route" is not defined.');
        } else {
            $routeName = $routeData['route'];
        }

        $params = isset($routeData['parameters']) ? $routeData['parameters'] : array();

        return new RedirectResponse(
            $this->router->generate(
                $routeName,
                $params,
                UrlGeneratorInterface::ABSOLUTE_PATH
            ),
            $status
        );
    }

    /**
     * Redirects to "Save and Stay" or "Save and Close" route depends on which button is clicked
     *
     * @param array  $saveAndStayRoute  A route data for "Save and Stay" button
     * @param array  $saveAndCloseRoute A route data for "Save and Close" button
     * @param object $entity            An entity was saved. Specify this parameter only if an entity
     *                                  is ACL controlled and you want to redirect to "Save and Close" route when
     *                                  a new entity is created and an user have no permissions to edit this entity.
     *                                  Please note that if this parameter is not specified, an user clicks
     *                                  "Save and Stay" and he/she does not have permissions to edit an entity
     *                                  an access denied error happens. So, be careful if you decide to not specify
     *                                  this parameter.
     * @return RedirectResponse
     * @throws \InvalidArgumentException If a route date is not valid
     */
    public function redirectAfterSave(array $saveAndStayRoute, array $saveAndCloseRoute, $entity = null)
    {
        if ($this->request->get(self::ACTION_PARAMETER) == self::ACTION_SAVE_AND_STAY &&
            ($entity === null || $this->securityFacade->isGranted('EDIT', $entity))
        ) {
            $routeData = $saveAndStayRoute;
        } else {
            $routeData = $saveAndCloseRoute;
        }

        if (!isset($routeData['route'])) {
            throw new \InvalidArgumentException('The "route" attribute must be defined.');
        }

        $params = isset($routeData['parameters'])
            ? $routeData['parameters']
            : [];

        return new RedirectResponse(
            $this->router->generate($routeData['route'], $params)
        );
    }
}
