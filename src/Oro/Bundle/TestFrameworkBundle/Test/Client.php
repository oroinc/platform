<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Bundle\FrameworkBundle\Client as BaseClient;

class Client extends BaseClient
{
    const LOCAL_URL = 'http://localhost';

    /**
     * @var PDOConnection
     */
    protected $pdoConnection;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var boolean
     */
    protected $hasPerformedRequest;

    /**
     * @var boolean[]
     */
    protected $loadedFixtures;

    /**
     * {@inheritdoc}
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
     * @param array|string $gridParameters
     * @param array $filter
     * @return Response
     */
    public function requestGrid($gridParameters, $filter = array())
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

        $this->request(
            'GET',
            $this->getUrl('oro_datagrid_index', $gridParameters)
        );

        return $this->getResponse();
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    protected function getUrl($name, $parameters = array(), $absolute = false)
    {
        return $this->getContainer()->get('router')->generate($name, $parameters, $absolute);
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

        $this->refreshDoctrineConnection();

        $response = $this->kernel->handle($request);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
        return $response;
    }

    /**
     * Refresh doctrine connection services
     */
    protected function refreshDoctrineConnection()
    {
        if (!$this->pdoConnection) {
            return;
        }

        /** @var \Doctrine\DBAL\Connection $oldConnection */
        $oldConnection = $this->getContainer()->get('doctrine.dbal.default_connection');

        $newConnection =  $this->getContainer()->get('doctrine.dbal.connection_factory')
            ->createConnection(
                array_merge($oldConnection->getParams(), array('pdo' => $this->pdoConnection)),
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

    /**
     * Start transaction
     */
    public function startTransaction()
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.dbal.default_connection');
        $this->pdoConnection = $connection->getWrappedConnection();
        $this->pdoConnection->beginTransaction();

        $this->refreshDoctrineConnection();
    }

    /**
     * Rollback transaction
     */
    public function rollbackTransaction()
    {
        if ($this->pdoConnection) {
            $this->pdoConnection->rollBack();
        }
    }
}
