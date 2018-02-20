<?php

namespace Oro\Bundle\IntegrationBundle\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse as Response;
use Symfony\Component\Yaml\Yaml;

class FakeRestClientFactory implements RestClientFactoryInterface
{
    const DEFAULT_RESPONSE = '__DEFAULT__';

    /** @var string Fixture file path */
    protected $fixtureFile;

    /**
     * {@inheritdoc}
     */
    public function createRestClient($baseUrl, array $defaultOptions)
    {
        $fakeClient = new FakeRestClient();
        $this->loadFixture($fakeClient);

        return $fakeClient;
    }

    /**
     * @param string $fixtureFile
     */
    public function setFixtureFile($fixtureFile)
    {
        $this->fixtureFile = $fixtureFile;
    }

    /**
     * @return array returns array responses from fixture
     */
    protected function loadFixture(FakeRestClient $fakeClient)
    {
        $responseList = [];
        $fixtureResponseList = static::getFixturesFromFile($this->fixtureFile);

        foreach ($fixtureResponseList as $resourceUrl => $response) {
            $headers = is_array($response['headers']) ? $response['headers'] : [];

            $responseList[$resourceUrl] = new Response(
                $response['code'],
                $headers,
                json_encode($response['body'])
            );
        }

        if (isset($responseList[static::DEFAULT_RESPONSE])) {
            $fakeClient->setDefaultResponse($responseList[static::DEFAULT_RESPONSE]);
            unset($responseList[static::DEFAULT_RESPONSE]);
        }

        $fakeClient->setResponseList($responseList);
    }

    /**
     * Returns array of fixtures from provided YAML file
     *
     * @param $fixtureFileName
     *
     * @return array
     */
    public static function getFixturesFromFile($fixtureFileName)
    {
        $fixtureResponseList = [];

        if (null !== $fixtureFileName && file_exists($fixtureFileName)) {
            /** @var array $fixtureResponseList */
            $fixtureResponseList = Yaml::parse(file_get_contents($fixtureFileName));
        }

        return $fixtureResponseList;
    }
}
