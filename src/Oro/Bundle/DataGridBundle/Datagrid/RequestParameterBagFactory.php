<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Symfony\Component\HttpFoundation\RequestStack;

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

    /**
     * @param string $parametersClass
     * @param RequestStack $requestStack
     */
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
        $parameters = $request ? $request->get($gridParameterName, []) : [];

        if (!is_array($parameters)) {
            $parameters = [];
        }

        $minifiedParameters = $this->getMinifiedParameters($gridParameterName);
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
