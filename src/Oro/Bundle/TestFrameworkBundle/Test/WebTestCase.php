<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

/**
 * Abstract class for functional and integration tests
 */
abstract class WebTestCase extends BaseWebTestCase
{
    /** Annotation names */
    const DB_ISOLATION_ANNOTATION = 'dbIsolation';
    const DB_REINDEX_ANNOTATION = 'dbReindex';

    /** Default WSSE credentials */
    const USER_NAME = 'admin';
    const USER_PASSWORD = 'admin_api_key';

    /**  Default user name and password */
    const AUTH_USER = 'admin@example.com';
    const AUTH_PW = 'admin';

    /**
     * @var bool[]
     */
    static private $dbIsolation;

    /**
     * @var bool[]
     */
    static private $dbReindex;

    /**
     * @var Client
     */
    static protected $internalClient;

    protected function tearDown()
    {
        $refClass = new \ReflectionClass($this);
        foreach ($refClass->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }

    /**
     * Creates a Client.
     *
     * @param array $options An array of options to pass to the createKernel class
     * @param array $server  An array of server parameters
     * @return Client A Client instance
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        if (!self::$internalClient) {
            self::$internalClient = parent::createClient($options, $server);

            if (self::getDbIsolationSetting()) {
                /** @var Client $client */
                $client = self::$internalClient;

                //workaround MyISAM search tables are not on transaction
                if (self::getDbIsolationSetting()) {
                    $kernel = $client->getKernel();
                    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
                    $application->setAutoExit(false);
                    $options = array('command' => 'oro:search:reindex');
                    $options['--env'] = "test";
                    $options['--quiet'] = null;
                    $application->run(new \Symfony\Component\Console\Input\ArrayInput($options));
                }

                $client->startTransaction();
                $pdoConnection = Client::getPdoConnection();
                if ($pdoConnection) {
                    //set transaction level to 1 for entityManager
                    $connection = $client->createConnection($pdoConnection);
                    $client->getContainer()->set('doctrine.dbal.default_connection', $connection);

                    /** @var EntityManager $entityManager */
                    $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
                    if (spl_object_hash($entityManager->getConnection()) != spl_object_hash($connection)) {
                        $reflection = new \ReflectionProperty('Doctrine\ORM\EntityManager', 'conn');
                        $reflection->setAccessible(true);
                        $reflection->setValue($entityManager, $connection);
                    }
                }
            }
        } else {
            self::$internalClient->setServerParameters($server);
        }

        return self::$internalClient;
    }

    public static function tearDownAfterClass()
    {
        if (self::$internalClient) {
            /** @var Client $client */
            $client = self::$internalClient;
            if (self::getDbIsolationSetting()) {
                $client->rollbackTransaction();
            }
            $client->setSoapClient(null);
            self::$internalClient = null;
        }
    }

    /**
     * Get value of dbIsolation option from annotation of called class
     *
     * @return bool
     */
    protected static function getDbIsolationSetting()
    {
        $calledClass = get_called_class();
        if (!isset(self::$dbIsolation[$calledClass])) {
            self::$dbIsolation[$calledClass] = self::isClassHasAnnotation($calledClass, self::DB_ISOLATION_ANNOTATION);
        }

        return self::$dbIsolation[$calledClass];
    }

    /**
     * Get value of dbIsolation option from annotation of called class
     *
     * @return bool
     */
    protected static function getDbReindexSetting()
    {
        $calledClass = get_called_class();
        if (!isset(self::$dbReindex[$calledClass])) {
            self::$dbReindex[$calledClass] = self::isClassHasAnnotation($calledClass, self::DB_REINDEX_ANNOTATION);
        }

        return self::$dbReindex[$calledClass];
    }

    /**
     * @param string $className
     * @param string $annotationName
     * @return bool
     */
    protected static function isClassHasAnnotation($className, $annotationName)
    {
        $annotations = \PHPUnit_Util_Test::parseTestMethodAnnotations($className);
        return isset($annotations['class'][$annotationName]);
    }

    /**
     * @return Client
     * @throws \BadMethodCallException
     */
    public static function getClientInstance()
    {
        if (!self::$internalClient) {
            throw new \BadMethodCallException('Client instance is not initialized.');
        }

        return self::$internalClient;
    }

    /**
     * Attempts to guess the kernel location.
     *
     * When the Kernel is located, the file is required.
     *
     * @return string The Kernel class name
     * @throws \RuntimeException
     */
    protected static function getKernelClass()
    {
        $dir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : static::getPhpUnitXmlDir();

        $finder = new Finder();
        $finder->name('AppKernel.php')->depth(0)->in($dir);
        $results = iterator_to_array($finder);
        if (!count($results)) {
            throw new \RuntimeException(
                'Either set KERNEL_DIR in your phpunit.xml according to ' .
                'http://symfony.com/doc/current/book/testing.html#your-first-functional-test ' .
                'or override the WebTestCase::createKernel() method.'
            );
        }

        $file = current($results);
        $class = $file->getBasename('.php');

        require_once $file;

        return $class;
    }

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
     * @param Client $client
     * @param array|string $gridParameters
     * @param array $filter
     * @return Response
     */
    public static function getGridResponse(Client $client, $gridParameters, $filter = array())
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
        $client->request(
            'GET',
            $client->generate('oro_datagrid_index', $gridParameters)
        );

        return $client->getResponse();
    }

    /**
     * Data provider for REST/SOAP API tests
     *
     * @param string $folder
     * @return array
     */
    public static function getApiRequestsData($folder)
    {
        static $randomString;

        // generate unique value
        if (!$randomString) {
            $randomString = self::generateRandomString(5);
        }

        $parameters = array();
        $testFiles = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);
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

        return $parameters;
    }

    /**
     * Convert value to array
     *
     * @param mixed $value
     * @return array
     */
    public static function valueToArray($value)
    {
        return (array)json_decode(json_encode($value), true);
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
     * Checks json response status code and return content as array
     *
     * @param Response $response
     * @param int $statusCode
     * @return array
     */
    public static function getJsonResponseContent(Response $response, $statusCode)
    {
        self::assertJsonResponseStatusCodeEquals($response, $statusCode);
        return self::jsonToArray($response->getContent());
    }

    /**
     * Assert response is json and has status code
     *
     * @param Response $response
     * @param int $statusCode
     */
    public static function assertJsonResponseStatusCodeEquals(Response $response, $statusCode)
    {
        self::assertResponseStatusCodeEquals($response, $statusCode);
        self::assertResponseContentTypeEquals($response, 'application/json');
    }

    /**
     * Assert response is html and has status code
     *
     * @param Response $response
     * @param int $statusCode
     */
    public static function assertHtmlResponseStatusCodeEquals(Response $response, $statusCode)
    {
        self::assertResponseStatusCodeEquals($response, $statusCode);
        self::assertResponseContentTypeEquals($response, 'text/html; charset=UTF-8');
    }

    /**
     * Assert response status code equals
     *
     * @param Response $response
     * @param int $statusCode
     */
    public static function assertResponseStatusCodeEquals(Response $response, $statusCode)
    {
        \PHPUnit_Framework_TestCase::assertEquals(
            $statusCode,
            $response->getStatusCode(),
            $response->getContent()
        );
    }

    /**
     * Assert response content type equals
     *
     * @param Response $response
     * @param string $contentType
     */
    public static function assertResponseContentTypeEquals(Response $response, $contentType)
    {
        \PHPUnit_Framework_TestCase::assertTrue(
            $response->headers->contains('Content-Type', $contentType),
            $response->headers
        );
    }
}
