<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Datagrid property formatter for rendering a URL
 */
class UrlProperty extends AbstractProperty
{
    const ROUTE_KEY       = 'route';
    const IS_ABSOLUTE_KEY = 'isAbsolute';
    const ANCHOR_KEY      = 'anchor';
    const PARAMS_KEY      = 'params';
    const DIRECT_PARAMS_KEY = 'direct_params';

    /** @var array */
    protected $excludeParams = [self::ROUTE_KEY, self::IS_ABSOLUTE_KEY, self::ANCHOR_KEY, self::PARAMS_KEY];

    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawValue(ResultRecordInterface $record)
    {
        $referenceType = $this->getOr(self::IS_ABSOLUTE_KEY, false)
            ? UrlGeneratorInterface::ABSOLUTE_URL
            : UrlGeneratorInterface::ABSOLUTE_PATH;

        $route = $this->router->generate(
            $this->get(self::ROUTE_KEY),
            $this->getParameters($record),
            $referenceType
        );

        return $route . $this->getOr(self::ANCHOR_KEY);
    }

    /**
     * Get route parameters from record
     *
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    protected function getParameters(ResultRecordInterface $record)
    {
        $result = [];
        foreach ($this->getOr(self::PARAMS_KEY, []) as $name => $dataKey) {
            if (is_numeric($name)) {
                $name = $dataKey;
            }
            $result[$name] = $record->getValue($dataKey);
        }

        return array_merge($result, $this->getOr(static::DIRECT_PARAMS_KEY, []));
    }
}
