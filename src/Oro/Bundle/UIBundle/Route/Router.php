<?php

namespace Oro\Bundle\UIBundle\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\HttpFoundation\RedirectResponse;


use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class Router
{
    const ACTION_PARAMETER     = 'input_action';
    const ACTION_SAVE_AND_STAY = 'save_and_stay';
    const ACTION_SAVE_CLOSE = 'save_and_close';

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

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param Request        $request
     * @param SymfonyRouter  $router
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        Request $request,
        SymfonyRouter $router,
        SecurityFacade $securityFacade
    ) {
        $this->request = $request;
        $this->router = $router;
        $this->securityFacade = $securityFacade;

        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * Redirects to "Save and Stay" or "Save and Close" route depends on which button is clicked
     *
     * @param array  $saveAndStayRoute  A route data for "Save and Stay" button
     * @param array  $saveAndCloseRoute A route data for "Save and Close" button
     * @param object $entity            An entity was saved. Specify this parameter only if an entity
     *                                  is ACL protected and you want to redirect to "Save and Close" route when
     *                                  a new entity is created and an user have no permissions to edit this entity.
     *                                  Please note that if this parameter is not specified, an user clicks
     *                                  "Save and Stay" and he/she does not have permissions to edit an entity
     *                                  an access denied error happens. So, be careful if you decide to not specify
     *                                  this parameter.
     * @return RedirectResponse
     * @throws \InvalidArgumentException If a route date is not valid
     * @deprecated Will be removed in 1.11. Use redirectToAfterSaveAction instead
     */
    public function redirectAfterSave(array $saveAndStayRoute = [], array $saveAndCloseRoute = [], $entity = null)
    {
        switch ($this->request->get(self::ACTION_PARAMETER)) {
            case self::ACTION_SAVE_AND_STAY:
                /**
                 * If user has no permission to edit Save and close callback should be used
                 */
                if (is_null($entity) || $this->securityFacade->isGranted('EDIT', $entity)) {
                    $routeData = $saveAndStayRoute;
                } else {
                    $routeData = $saveAndCloseRoute;
                }

                break;
            case self::ACTION_SAVE_CLOSE:
                $routeData = $saveAndCloseRoute;

                break;
            default:
                /**
                 * Avoids of BC break
                 */
                return $this->redirectToAfterSaveAction($entity);
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

    /**
     * @param array|object|null $context
     *
     * @return RedirectResponse
     */
    public function redirectToAfterSaveAction($context)
    {
        $route = $this->request->get(self::ACTION_PARAMETER);
        if (empty($route)) {
            throw new \InvalidArgumentException('The "input_action" parameter required');
        }

        $route = json_decode($route, true);
        if (empty($route['route'])) {
            throw new \InvalidArgumentException('The "route" attribute required');
        }

        $routeParams = [];
        if (isset($route['params'])) {
            $routeParams = $route['params'];
            if ($context) {
                $resolveEntityRelatedParamsCallback = function ($parameter) use ($context) {
                    if (strpos($parameter, '$.') === 0) {
                        $parameterParts = explode('$.', $parameter, 2);
                        return $this->propertyAccessor->getValue($context, $parameterParts[1]);
                    }

                    return $parameter;
                };

                $routeParams = array_map($resolveEntityRelatedParamsCallback, $routeParams);
            }
        }

        return new RedirectResponse(
            $this->router->generate($route['route'], $routeParams)
        );
    }
}
