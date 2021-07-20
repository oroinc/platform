<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Creates datagrid ParameterBag based on the request query.
 */
class RequestParameterBagFactory
{
    const DEFAULT_ROOT_PARAM = 'grid';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $parametersClass;

    public function __construct(string $parametersClass, RequestStack $requestStack)
    {
        $this->parametersClass = $parametersClass;
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $gridParameterName
     *
     * @return array
     */
    public function fetchParameters($gridParameterName = self::DEFAULT_ROOT_PARAM)
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request ? $this->fetchParametersFromRequest($request, $gridParameterName) : [];
    }

    public function fetchParametersFromRequest(
        Request $request,
        string $gridParameterName = self::DEFAULT_ROOT_PARAM
    ): array {
        $parameters = $request->get($gridParameterName, []);
        if (!is_array($parameters)) {
            $parameters = [];
        }

        $minifiedParameters = $this->getMinifiedParametersFromRequest($request, $gridParameterName);
        if ($minifiedParameters) {
            $parameters[ParameterBag::MINIFIED_PARAMETERS] = $minifiedParameters;
        }

        return $parameters;
    }

    /**
     * @param string $gridParameterName
     * @return ParameterBag
     */
    public function createParameters($gridParameterName = self::DEFAULT_ROOT_PARAM)
    {
        $parameters = $this->fetchParameters($gridParameterName);

        return new $this->parametersClass($parameters);
    }

    public function createParametersFromRequest(
        Request $request,
        string $gridParameterName = self::DEFAULT_ROOT_PARAM
    ): ParameterBag {
        $parameters = $this->fetchParametersFromRequest($request, $gridParameterName);

        return new $this->parametersClass($parameters);
    }

    private function getMinifiedParametersFromRequest(Request $request, string $gridParameterName): array
    {
        $gridData = $request->get(self::DEFAULT_ROOT_PARAM, []);

        if (!empty($gridData[$gridParameterName]) && is_string($gridData[$gridParameterName])) {
            parse_str($gridData[$gridParameterName], $parameters);
        }

        return $parameters ?? [];
    }

    /**
     * @param string $gridParameterName
     * @return null
     */
    protected function getMinifiedParameters($gridParameterName)
    {
        $request = $this->requestStack->getCurrentRequest();
        $gridData = $request ? $request->get(self::DEFAULT_ROOT_PARAM, []) : [];
        if (empty($gridData[$gridParameterName])) {
            return null;
        }

        parse_str($gridData[$gridParameterName], $parameters);

        return $parameters;
    }
}
