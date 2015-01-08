<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use FOS\RestBundle\Util\Codes;

class OroTranslationAdapter extends AbstractAPIAdapter
{
    const URL_STATS    = 'stats';
    const URL_DOWNLOAD = 'download';

    /**
     * {@inheritdoc}
     */
    public function download($path, array $projects = [], $package = null)
    {
        $package = is_null($package) ? 'all' : str_replace('_', '-', $package);
        $result  = $this->request(
            self::URL_DOWNLOAD,
            [
                'packages' => implode(',', $projects),
                'lang'     => $package,
            ],
            'GET',
            [
                'save_to'           => $path,
            ]
        );

        return 200 == $result->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function upload($files, $mode = 'add')
    {
        throw new \Exception('Adapter not support this method');
    }

    /**
     * Fetch statistic
     *
     * @param array $packages
     *
     * @throws \RuntimeException
     * @return array [
     *     [
     *         'code' => 'en_US',
     *         'realCode' => 'en',
     *         'translationStatus' => 30,
     *         'lastBuildDate' => \DateTime::ISO8601 - string
     *     ]
     * ]
     *  RealCode - language code for download API (not used in BAP)
     *  Code     - 5 symbols locale code
     */
    public function fetchStatistic(array $packages = [])
    {
        $response = $this->request(
            self::URL_STATS,
            ['packages' => implode(',', $packages)]
        );

        if ($response->getStatusCode() === Codes::HTTP_OK) {
            $result = $response->json();
            $result = is_array($result) ? $result : [];

            $filtered = array_filter(
                $result,
                function ($item) {
                    return isset($item['code']) && isset($item['translationStatus']) && isset($item['lastBuildDate']);
                }
            );

            if (empty($filtered) || empty($result)) {
                $this->logger->critical('Bad data received' . PHP_EOL . var_export($result, true));
                throw new \RuntimeException('Bad data received');
            }

            return $result;
        } else {
            $this->logger->critical('Service unavailable. Status received: ' . $response->getStatusCode());
            throw new \RuntimeException('Service unavailable');
        }
    }
}
