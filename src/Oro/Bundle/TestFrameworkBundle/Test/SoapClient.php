<?php
namespace Oro\Bundle\TestFrameworkBundle\Test;

use \SoapClient as BasicSoapClient;

class SoapClient extends BasicSoapClient
{
    /** @var \Symfony\Bundle\FrameworkBundle\Client */
    protected $client;

    /**
     * Overridden constructor
     *
     * @param string $wsdl
     * @param array $options
     * @param \Symfony\Bundle\FrameworkBundle\Client $client
     */
    public function __construct($wsdl, $options, &$client)
    {
        $this->client = $client;
        parent::__construct($wsdl, $options);
    }

    public function __destruct()
    {
        unset($this->client);
    }

    /**
     * Overridden _doRequest method
     *
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int $one_way
     *
     * @return string
     * @throws \Exception
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        //make POST request
        $this->client->request(
            'POST',
            (string)$location,
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/soap+xml'
            ),
            (string)$request
        );

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 500) {
            throw new \Exception($content, $statusCode);
        }

        return $content;
    }
}
