<?php

namespace Oro\Bundle\UIBundle\Route;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a set of methods that help processing redirects in CRUD controllers.
 */
class Router
{
    public const ACTION_PARAMETER = 'input_action';

    protected RequestStack $requestStack;
    protected UrlGeneratorInterface $urlGenerator;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->authorizationChecker = $authorizationChecker;

        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax();
    }

    /**
     * @param array|object|null $context
     */
    public function redirect(mixed $context): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        $rawRouteData = $this->getInputActionData($request);

        /**
         * Default route should be used in case if no input_action in request
         */
        if (!is_array($rawRouteData)) {
            return new RedirectResponse($request->getUri());
        }

        if ($this->hasRedirectUrl($rawRouteData)) {
            $redirectUrl = $this->parseRedirectUrl($rawRouteData);
        } else {
            $routeName = $this->parseRouteName($rawRouteData);
            $routeParams = $this->parseRouteParams($rawRouteData, $context);
            $routeParams = $this->mergeRequestQueryParams($routeParams);
            $redirectUrl = $this->urlGenerator->generate($routeName, $routeParams);
        }
        return new RedirectResponse($redirectUrl);
    }

    public function getInputActionData(Request $request = null): ?array
    {
        if ($request === null) {
            $request = $this->requestStack->getCurrentRequest();
        }

        return json_decode($this->getRawRouteData($request), true);
    }

    /**
     * Gets data for routing from request parameter with name self::ACTION_PARAMETER
     *
     * Expected input value is a JSON value with keys "route" (required) and "params" (optional).
     * For example:
     * <code>
     *  {"route": "some_route_name", "params": {"some_parameter_name": "some_value"}}
     * </code>
     *
     * @return string|null JSON string representing raw route data taken from request.
     */
    private function getRawRouteData(Request $request):? string
    {
        $result = $request->get(self::ACTION_PARAMETER);

        if ($result && !is_string($result)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Request parameter "%s" must be string, %s is given.',
                    self::ACTION_PARAMETER,
                    is_object($result) ? get_class($result) : gettype($result)
                )
            );
        }

        return $result;
    }

    /**
     * Parses value of route name.
     */
    private function parseRouteName(array $arrayData): string
    {
        if (empty($arrayData['route'])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot parse route name from request parameter "%s". Value of key "%s" cannot be empty: %s',
                    self::ACTION_PARAMETER,
                    'route',
                    json_encode($arrayData)
                )
            );
        }

        if (!is_string($arrayData['route'])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot parse route name from request parameter "%s". Value of key "%s" must be string: %s',
                    self::ACTION_PARAMETER,
                    'route',
                    json_encode($arrayData)
                )
            );
        }

        return $arrayData['route'];
    }

    /**
     * Check redirectUrl for existence.
     */
    private function hasRedirectUrl(array $arrayData): string
    {
        return !empty($arrayData['redirectUrl']);
    }

    /**
     * Parses redirectUrl.
     */
    private function parseRedirectUrl(array $arrayData): string
    {
        if (empty($arrayData['redirectUrl'])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot parse route name from request parameter "%s". Value of key "%s" cannot be empty: %s',
                    self::ACTION_PARAMETER,
                    'redirectUrl',
                    json_encode($arrayData)
                )
            );
        }

        if (!is_string($arrayData['redirectUrl'])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot parse route name from request parameter "%s". Value of key "%s" must be string: %s',
                    self::ACTION_PARAMETER,
                    'redirectUrl',
                    json_encode($arrayData)
                )
            );
        }

        // check for malformed URL
        if (parse_url($arrayData['redirectUrl']) === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot parse route name from request parameter "%s". Value of key "%s" is not valid URL',
                    self::ACTION_PARAMETER,
                    'redirectUrl'
                )
            );
        }

        return $arrayData['redirectUrl'];
    }

    /**
     * Parses value of route parameters.
     *
     * @param array $arrayData
     * @param array|object|null $context
     *
     * @return mixed
     */
    private function parseRouteParams(array $arrayData, mixed $context): mixed
    {
        if (empty($arrayData['params'])) {
            return [];
        }
        if (!is_array($arrayData['params'])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot parse route name from request parameter "%s". Value of key "%s" must be array: %s',
                    self::ACTION_PARAMETER,
                    'params',
                    json_encode($arrayData)
                )
            );
        }

        $parsedParameters = $arrayData['params'];
        if (!$context) {
            return $parsedParameters;
        }

        foreach ($arrayData['params'] as $parameterName => $parameterValue) {
            $parsedParameters[$parameterName] = $this->parseRouteParam($parameterValue, $context);
        }

        return $parsedParameters;
    }

    /**
     * Returns list of passed route parameters merged with query parameters of current request.
     */
    private function mergeRequestQueryParams(array $routeParams): array
    {
        $queryParams = $this->requestStack->getCurrentRequest()->query->all();

        if ($queryParams) {
            $routeParams = array_merge($queryParams, $routeParams);
        }

        return $routeParams;
    }

    /**
     * Parses parameter passed to router data.
     * Considers a value of parameter as a property path if it starts from '$'. For that case value of this property
     * will be taken from $context
     */
    private function parseRouteParam($parameterValue, mixed $context): mixed
    {
        if (is_string($parameterValue) && str_starts_with($parameterValue, '$')) {
            return $this->propertyAccessor->getValue($context, substr($parameterValue, 1));
        }

        return $parameterValue;
    }
}
