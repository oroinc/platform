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
        $this->client =  $client;
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
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        //save directly in _SERVER array
        $_SERVER['HTTP_SOAPACTION'] = $action;
        $_SERVER['CONTENT_TYPE'] = 'application/soap+xml';
        //make POST request
        $this->client->request('POST', (string)$location, array(), array(), array(), (string)$request);
        unset($_SERVER['HTTP_SOAPACTION']);
        unset($_SERVER['CONTENT_TYPE']);
        return $this->client->getResponse()->getContent();
    }
}
