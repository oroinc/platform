<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Symfony\Component\HttpFoundation\Request;

class RequestParameterBagFactory
{
    const DEFAULT_ROOT_PARAM = 'grid';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $parametersClass;

    /**
     * @param string $parametersClass
     */
    public function __construct($parametersClass)
    {
        $this->parametersClass = $parametersClass;
    }

    /**
     * @param string $gridParameterName
     * @return ParameterBag
     */
    public function createParameters($gridParameterName = self::DEFAULT_ROOT_PARAM)
    {
        $parameters = $this->request->get($gridParameterName, []);

        if (!is_array($parameters)) {
            $parameters = array();
        }

        $minifiedParameters = $this->getMinifiedParameters($gridParameterName);
        if ($minifiedParameters) {
            $parameters[ParameterBag::MINIFIED_PARAMETERS] = $minifiedParameters;
        }

        return new $this->parametersClass($parameters);
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        if ($request instanceof Request) {
            $this->request = $request;
        }
    }

    /**
     * @param string $gridParameterName
     * @return null
     */
    protected function getMinifiedParameters($gridParameterName)
    {
        $gridData = $this->request->get(self::DEFAULT_ROOT_PARAM, array());
        if (empty($gridData[$gridParameterName])) {
            return null;
        }

        parse_str($gridData[$gridParameterName], $parameters);

        return $parameters;
    }
}
