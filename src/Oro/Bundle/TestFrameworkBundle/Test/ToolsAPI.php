<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Response;

class ToolsAPI
{
    /** Default WSSE credentials */
    const USER_NAME = 'admin';
    const USER_PASSWORD = 'admin_api_key';

    /**  Default user name and password */
    const AUTH_USER = 'admin@example.com';
    const AUTH_PW = 'admin';

    /**
     * Generate WSSE authorization header
     *
     * @param string $userName
     * @param string $userPassword
     * @param string|null $nonce
     * @return array
     */
    public static function generateWsseHeader(
        $userName = self::USER_NAME,
        $userPassword = self::USER_PASSWORD,
        $nonce = null
    ) {
        if (null === $nonce) {
            $nonce = uniqid();
        }

        $created  = date('c');
        $digest   = base64_encode(sha1(base64_decode($nonce) . $created . $userPassword, true));
        $wsseHeader = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'WSSE profile="UsernameToken"',
            'HTTP_X-WSSE' => sprintf(
                'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
                $userName,
                $digest,
                $nonce,
                $created
            )
        );
        return $wsseHeader;
    }

    /**
     * Generate Basic  authorization header
     *
     * @param string $userName
     * @param string $userPassword
     * @return array
     */
    public static function generateBasicHeader($userName = self::AUTH_USER, $userPassword = self::AUTH_PW)
    {
        return array('PHP_AUTH_USER' =>  $userName, 'PHP_AUTH_PW' => $userPassword);
    }

    /**
     * Data provider for REST/SOAP API tests
     *
     * @param string $folder
     * @return array
     */
    public static function requestsApi($folder)
    {
        static $randomString;

        // generate unique value
        if (!$randomString) {
            $randomString = self::generateRandomString(5);
        }

        $parameters = array();
        $testFiles = new \RecursiveDirectoryIterator(
            $folder,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        foreach ($testFiles as $fileName => $object) {
            $parameters[$fileName] = Yaml::parse($fileName);
            if (is_null($parameters[$fileName]['response'])) {
                unset($parameters[$fileName]['response']);
            }
        }

        $replaceCallback = function (&$value) use ($randomString) {
            if (!is_null($value)) {
                $value = str_replace('%str%', $randomString, $value);
            }
        };

        foreach ($parameters as $key => $value) {
            array_walk(
                $parameters[$key]['request'],
                $replaceCallback,
                $randomString
            );
            array_walk(
                $parameters[$key]['response'],
                $replaceCallback,
                $randomString
            );
        }

        return
            $parameters;
    }

    /**
     * Test API response
     *
     * @param array $response
     * @param string $result
     * @param string $message
     */
    public static function assertEqualsResponse($response, $result, $message = '')
    {
        \PHPUnit_Framework_TestCase::assertEquals($response['return'], $result, $message);
    }

    /**
     * Test API response status
     *
     * @param Response $response
     * @param int $statusCode
     * @param string|false $headerContentType
     */
    public static function assertJsonResponse(
        Response $response,
        $statusCode = 201,
        $headerContentType = 'application/json'
    ) {
        \PHPUnit_Framework_TestCase::assertEquals(
            $statusCode,
            $response->getStatusCode(),
            $response->getContent()
        );

        if ($headerContentType) {
            \PHPUnit_Framework_TestCase::assertTrue(
                $response->headers->contains('Content-Type', $headerContentType),
                $response->headers
            );
        }
    }

    /**
     * Convert stdClass to array
     *
     * @param string $class
     * @return array
     */
    public static function classToArray($class)
    {
        return json_decode(json_encode($class), true);
    }

    /**
     * Convert json to array
     *
     * @param string $json
     * @return array
     */
    public static function jsonToArray($json)
    {
        return json_decode($json, true);
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 10)
    {
        $random= "";
        srand((double) microtime() * 1000000);
        $char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $char_list .= "abcdefghijklmnopqrstuvwxyz";
        $char_list .= "1234567890_";

        for ($i = 0; $i < $length; $i++) {
            $random .= substr($char_list, (rand() % (strlen($char_list))), 1);
        }

        return $random;
    }

    /**
     * @param Client $test
     * @param array|string $gridParameters
     * @param array $filter
     * @return Response
     */
    public static function getEntityGrid(Client $test, $gridParameters, $filter = array())
    {
        if (is_string($gridParameters)) {
            $gridParameters = array('gridName' => $gridParameters);
        }

        //transform parameters to nested array
        $parameters = array();
        foreach ($filter as $param => $value) {
            $param .= '=' . $value;
            parse_str($param, $output);
            $parameters = array_merge_recursive($parameters, $output);
        }

        $gridParameters = array_merge_recursive($gridParameters, $parameters);
        $test->request(
            'GET',
            $test->generate('oro_datagrid_index', $gridParameters)
        );

        return $test->getResponse();
    }
}
