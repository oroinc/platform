<?php

namespace Oro\Bundle\NavigationBundle\Utils;

use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;

/**
 * Normalizes PinbarTab URL before it is persisted to database.
 */
class PinbarTabUrlNormalizer implements PinbarTabUrlNormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNormalizedUrl(string $url): string
    {
        $urlQuery = parse_url($url, PHP_URL_QUERY);
        parse_str($urlQuery, $urlQueryArray);

        $this->normalizeDatagridParameters($urlQueryArray);

        $this->sortRecursive($urlQueryArray);

        return str_replace($urlQuery, http_build_query($urlQueryArray), $url);
    }

    private function normalizeDatagridParameters(array &$urlQueryArray): void
    {
        if (isset($urlQueryArray[RequestParameterBagFactory::DEFAULT_ROOT_PARAM])) {
            foreach ($urlQueryArray[RequestParameterBagFactory::DEFAULT_ROOT_PARAM] as $gridName => $parameters) {
                if (!\is_string($parameters)) {
                    continue;
                }

                parse_str($parameters, $parametersArray);
                $this->sortRecursive($parametersArray);

                $urlQueryArray[RequestParameterBagFactory::DEFAULT_ROOT_PARAM][$gridName] =
                    http_build_query($parametersArray);
            }
        }
    }

    /**
     * @param mixed $array
     */
    private function sortRecursive(&$array): void
    {
        if (!\is_array($array)) {
            return;
        }

        ksort($array);
        array_walk($array, [$this, 'sortRecursive']);
    }
}
