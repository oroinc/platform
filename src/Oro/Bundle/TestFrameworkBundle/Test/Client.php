<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\DBAL\Connection;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Bundle\FrameworkBundle\Client as BaseClient;

class Client extends BaseClient
{
    const LOCAL_URL = 'http://localhost';

    /**
     * @var SoapClient
     */
    static protected $soapClient;

    /**
     * @var Connection
     */
    static public $connection = null;

    /**
     * @var \Doctrine\DBAL\Driver\PDOConnection shared PDO connection
     */
    static protected $pdoConnection = null;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var boolean
     */
    protected $hasPerformedRequest;

    public function __destruct()
    {
        $this->setSoapClient(null);
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        return $this->getContainer()->get('router')->generate($name, $parameters, $absolute);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $parameters
     * @param array $files
     * @param array $server
     * @param string $content
     * @param bool $changeHistory
     * @return Crawler
     */
    public function request(
        $method,
        $uri,
        array $parameters = array(),
        array $files = array(),
        array $server = array(),
        $content = null,
        $changeHistory = true
    ) {
        if (strpos($uri, 'http://') === false) {
            $uri = self::LOCAL_URL . $uri;
        }

        if ($this->getServerParameter('HTTP_X-WSSE', '') !== '' && !isset($server['HTTP_X-WSSE'])) {
            //generate new WSSE header
            parent::setServerParameters(WebTestCase::generateWsseAuthHeader());
        }

        return parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    /**
     * @param string $wsdl
     * @param array $options
     * @param bool $new
     * @return SoapClient
     * @throws \Exception
     */
    public function createSoapClient($wsdl = null, array $options = null, $new = false)
    {
        if (!self::$soapClient || $new) {
            if (is_null($wsdl)) {
                throw new \Exception('wsdl should not be NULL');
            }

            $this->request('GET', $wsdl);
            $status = $this->getResponse()->getStatusCode();
            $statusText = Response::$statusTexts[$status];
            if ($status >= 400) {
                throw new \Exception($statusText, $status);
            }

            $wsdl = $this->getResponse()->getContent();
            //save to file
            $file = tempnam(sys_get_temp_dir(), date("Ymd") . '_') . '.xml';
            $fl = fopen($file, "w");
            fwrite($fl, $wsdl);
            fclose($fl);

            self::$soapClient = new SoapClient($file, $options, $this);

            unlink($file);
        }

        return self::$soapClient;
    }

    /**
     * @return SoapClient
     */
    public function getSoapClient()
    {
        return self::$soapClient;
    }

    /**
     * @param SoapClient|null $value
     */
    public function setSoapClient(SoapClient $value = null)
    {
        self::$soapClient = $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRequest($request)
    {
        if ($this->hasPerformedRequest) {
            $this->kernel->shutdown();
            $this->kernel->boot();
        } else {
            $this->hasPerformedRequest = true;
        }

        $this->refreshConnection();

        $response = $this->kernel->handle($request);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
        return $response;
    }

    /**
     * @param string $folder
     * @param array $filter
     */
    public function appendFixtures($folder, $filter = null)
    {
        $loader = new Loader();
        $loader->loadFromDirectory($folder);
        $fixtures = array_values($loader->getFixtures());

        //filter fixtures by className
        if (!is_null($filter)) {
            $fixturesCount = count($fixtures);
            for ($i = 0; $i < $fixturesCount; $i++) {
                $fixture = $fixtures[$i];
                foreach ($filter as $flt) {
                    if (!strpos(get_class($fixture), $flt)) {
                        unset($fixtures[$i]);
                    }
                }
            }
        }

        //init fixture container
        foreach ($fixtures as $fixture) {
            if (method_exists($fixture, 'setContainer')) {
                $fixture->setContainer($this->getContainer());
            }
        }

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $executor = new ORMExecutor($em, new ORMPurger($em));
        $executor->execute($fixtures, true);
    }

    /**
     * @return Connection
     */
    protected function refreshConnection()
    {
        if (!self::$pdoConnection) {
            return;
        }

        /** @var \Doctrine\DBAL\Connection $oldConnection */
        $oldConnection = $this->getContainer()->get('doctrine.dbal.default_connection');

        $newConnection =  $this->getContainer()->get('doctrine.dbal.connection_factory')
            ->createConnection(
                array_merge($oldConnection->getParams(), array('pdo' => self::$pdoConnection)),
                $oldConnection->getConfiguration(),
                $oldConnection->getEventManager()
            );

        $this->getContainer()->set('doctrine.dbal.default_connection', $newConnection);

        //increment transaction level
        $reflection = new \ReflectionProperty('Doctrine\DBAL\Connection', '_transactionNestingLevel');
        $reflection->setAccessible(true);
        $reflection->setValue($newConnection, $oldConnection->getTransactionNestingLevel() + 1);

        //update connection of entity manager
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        if ($entityManager->getConnection() !== $newConnection) {
            $reflection = new \ReflectionProperty('Doctrine\ORM\EntityManager', 'conn');
            $reflection->setAccessible(true);
            $reflection->setValue($entityManager, $newConnection);
        }
    }

    public function startTransaction()
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.dbal.default_connection');
        self::$pdoConnection = $connection->getWrappedConnection();
        self::$pdoConnection->beginTransaction();

        $this->refreshConnection();
    }

    public function rollbackTransaction()
    {
        if (self::$pdoConnection) {
            self::$pdoConnection->rollBack();
        }
    }
}
