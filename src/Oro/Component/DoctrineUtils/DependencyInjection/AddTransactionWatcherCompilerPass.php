<?php

namespace Oro\Component\DoctrineUtils\DependencyInjection;

use Doctrine\DBAL\Connection;
use Oro\Component\DoctrineUtils\DBAL\ChainTransactionWatcher;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherAwareInterface;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherConfigurator;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherInterface;
use Oro\Component\Testing\Doctrine\PersistentConnection;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Searches transaction watchers tagger by the specified tag and if at least one watcher found
 * generates a special proxy for the specified DBAL connection (if the connection is not specified
 * the default one is used) and injects the watchers to it.
 */
class AddTransactionWatcherCompilerPass implements CompilerPassInterface
{
    private const CONNECTION_PROXY_CLASS = 'ConnectionProxy';

    /** @var string */
    private $transactionWatcherTag;

    /** @var string|null */
    private $connectionName;

    public function __construct(string $transactionWatcherTag, string $connectionName = null)
    {
        $this->transactionWatcherTag = $transactionWatcherTag;
        $this->connectionName = $connectionName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $watchers = $this->findTransactionWatchers($container);
        if (empty($watchers)) {
            return;
        }

        // find the DBAL connection
        $connectionName = $this->getConnectionName($container);
        $connectionDef = $this->getConnectionServiceDefinition($container, $connectionName);
        $options = $connectionDef->getArgument(0);

        // check that the connection class is "Doctrine\DBAL\Connection"
        // ("Oro\Component\Testing\Doctrine\PersistentConnection" for the test environment)
        // or one of its subtype
        $expectedConnectionClass = Connection::class;
        if ('test' === $container->getParameter('kernel.environment')) {
            $expectedConnectionClass = PersistentConnection::class;
        }
        $connectionWrapperClass = $options['wrapperClass'] ?? $expectedConnectionClass;
        if (!is_a($connectionWrapperClass, $expectedConnectionClass, true)) {
            throw new \RuntimeException(sprintf(
                'The DBAL connection "wrapperClass" "%s" has to be "%s" or its subtype.',
                $connectionWrapperClass,
                $expectedConnectionClass
            ));
        }

        // make sure that the connection proxy directory exists
        $proxyDir = $this->getProxyDir($container);
        $this->ensureProxyDirExists($proxyDir);

        // replace the connection class with the proxy
        $options['wrapperClass'] = $this->generateProxy(
            $proxyDir,
            $connectionWrapperClass,
            $container->getParameter('kernel.environment') === 'test'
        );
        $connectionDef->replaceArgument(0, $options);

        // create the chain watcher and inject it to the connection proxy
        $watcherServiceId = $this->createWatcher($container, $connectionName, $watchers);
        $connectionDef->addMethodCall(
            'setTransactionWatcher',
            [new Reference($watcherServiceId)]
        );
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return Reference[] [service id => service reference, ...]
     */
    private function findTransactionWatchers(ContainerBuilder $container): array
    {
        // find transaction watchers
        $watchers = [];
        $taggedServices = $container->findTaggedServiceIds($this->transactionWatcherTag);
        foreach ($taggedServices as $id => $taggedAttributes) {
            foreach ($taggedAttributes as $attributes) {
                $priority = $attributes['priority'] ?? 0;
                $watchers[$priority][$id] = new Reference($id);
            }
        }

        // sort by priority and flatten
        if (!empty($watchers)) {
            krsort($watchers);
            $watchers = array_merge(...array_values($watchers));
        }

        return $watchers;
    }

    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException if the Doctrine DBAL
     * layer is not configured as expected
     */
    private function getConnectionName(ContainerBuilder $container): string
    {
        $connectionName = $this->connectionName;
        if (!$connectionName) {
            $connectionName = $container->getParameter('doctrine.default_connection');
        }

        return $connectionName;
    }

    /**
     * @throws \InvalidArgumentException if the given connection is not configured
     * @throws \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException if the Doctrine DBAL
     * layer is not configured as expected
     */
    private function getConnectionServiceDefinition(ContainerBuilder $container, string $connectionName): Definition
    {
        $connections = $container->getParameter('doctrine.connections');
        if (!isset($connections[$connectionName])) {
            throw new \InvalidArgumentException(
                sprintf('The DBAL connection "%s" does not exist.', $connectionName)
            );
        }

        return $container->findDefinition($connections[$connectionName]);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $connectionName
     * @param Reference[]      $watchers
     *
     * @return string The service id of the transaction watcher
     *
     * @throws \RuntimeException if the watcher for the specified connection already exists
     */
    private function createWatcher(ContainerBuilder $container, string $connectionName, array $watchers)
    {
        $watcherServiceId = sprintf('oro.doctrine.connection.chain_transaction_watcher.%s', $connectionName);
        if ($container->hasAlias($watcherServiceId) || $container->hasDefinition($watcherServiceId)) {
            throw new \RuntimeException(sprintf(
                'The transaction watcher for the DBAL connection "%s" already exists.',
                $connectionName
            ));
        }

        if (count($watchers) === 1) {
            $targetWatcherServiceId = key($watchers);
            $container->setAlias($watcherServiceId, new Alias($targetWatcherServiceId, false));
        } else {
            $chainWatcherDef = $container->register($watcherServiceId, ChainTransactionWatcher::class);
            $chainWatcherDef->setPublic(false);
            $chainWatcherDef->addArgument(array_values($watchers));
        }

        return $watcherServiceId;
    }

    /**
     * @param string $proxyDir
     * @param string $connectionClass
     * @param bool   $isTestMode
     *
     * @return string The connection proxy fully-qualified class name
     *
     * @throws \RuntimeException if the creation of the proxy file failed
     */
    private function generateProxy(string $proxyDir, string $connectionClass, bool $isTestMode = false): string
    {
        if (!str_starts_with($connectionClass, '\\')) {
            $connectionClass = '\\' . $connectionClass;
        }

        $transactionWatcherInterface = '\\' . TransactionWatcherInterface::class;
        $transactionWatcherAwareInterface = '\\' . TransactionWatcherAwareInterface::class;
        $proxyNamespace = TransactionWatcherConfigurator::CONNECTION_PROXY_NAMESPACE;
        $proxyClass = self::CONNECTION_PROXY_CLASS . '_' . md5($connectionClass);
        $proxyFile = $proxyDir . DIRECTORY_SEPARATOR . $proxyClass . '.php';
        $startNestingLevel = 1;
        $endNestingLevel = 0;
        $startNestingLevelInComments = '1';
        $endNestingLevelInComments = '0';
        if ($isTestMode) {
            // this is required because functional tests are wrapped with an external DBAL transaction
            // as a result of dbIsolation and dbIsolationPerTest annotations
            $startNestingLevel = 2;
            $endNestingLevel = 1;
            $startNestingLevelInComments = '2 (only for the "test" environment; 1 for other environments)';
            $endNestingLevelInComments = '1 (only for the "test" environment; 0 for other environments)';
        }

        if (!is_file($proxyFile)) {
            $this->writeProxyFile(
                $proxyFile,
                <<<PHP
<?php
namespace $proxyNamespace;

class $proxyClass extends $connectionClass implements $transactionWatcherAwareInterface
{
    private \$transactionWatcher;

    private \$originalTransactionWatcherException;

    public function setTransactionWatcher($transactionWatcherInterface \$transactionWatcher = null)
    {
        \$this->transactionWatcher = \$transactionWatcher;
    }
    public function beginTransaction()
    {
        parent::beginTransaction();
        // the nesting level equal to $startNestingLevelInComments means that the root transaction is started,
        // for nested transactions the nesting level will be greater that $startNestingLevelInComments
        if (null !== \$this->transactionWatcher && \$this->getTransactionNestingLevel() === $startNestingLevel) {
            \$this->transactionWatcher->onTransactionStarted();
        }
    }
    public function commit()
    {
        parent::commit();
        // the nesting level equal to $endNestingLevelInComments means that the root transaction is committed,
        // for nested transactions the nesting level will be greater that $endNestingLevelInComments
        if (null !== \$this->transactionWatcher && \$this->getTransactionNestingLevel() === $endNestingLevel) {
            try {
                \$this->transactionWatcher->onTransactionCommitted();
            } catch (\Throwable \$exception) {
                // to avoid silent exception in case if error was occurred in transaction
                // original exception was saved and throw before `rollback` method called
                // @see \Doctrine\DBAL\Connection::transactional
                \$this->originalTransactionWatcherException = \$exception;
                throw \$exception;
            }
        }
    }
    public function rollBack()
    {
        // throw original exception that catched in `commit` method
        if (!\$this->isTransactionActive() && \$this->originalTransactionWatcherException) {
            \$exception = \$this->originalTransactionWatcherException;
            \$this->originalTransactionWatcherException = null;
            throw \$exception;
        }

        parent::rollBack();
        // the nesting level equal to $endNestingLevelInComments means that the root transaction is rolled back,
        // for nested transactions the nesting level will be greater that $endNestingLevelInComments
        if (null !== \$this->transactionWatcher && \$this->getTransactionNestingLevel() === $endNestingLevel) {
            \$this->transactionWatcher->onTransactionRolledback();
        }
    }
}
PHP
            );
        }

        return $proxyNamespace . '\\' . $proxyClass;
    }

    /**
     * @throws \RuntimeException if the proxy file cannot be written
     */
    private function writeProxyFile(string $file, string $content)
    {
        $tmpFile = @tempnam(dirname($file), basename($file));
        if (false === @file_put_contents($tmpFile, $content) || !@rename($tmpFile, $file)) {
            throw new \RuntimeException(
                sprintf('Failed to write the DBAL connection proxy file "%s".', $file)
            );
        }

        @chmod($file, 0666 & ~umask());
    }

    private function getProxyDir(ContainerBuilder $container): string
    {
        return
            TransactionWatcherConfigurator::getConnectionProxyRootDir($container->getParameter('kernel.cache_dir'))
            . DIRECTORY_SEPARATOR
            . TransactionWatcherConfigurator::CONNECTION_PROXY_NAMESPACE;
    }

    /**
     * @throws \RuntimeException if the proxy directory cannot be created
     */
    private function ensureProxyDirExists(string $proxyDir)
    {
        if (is_dir($proxyDir)) {
            return;
        }

        if (!@mkdir($proxyDir, 0777, true) && !is_dir($proxyDir)) {
            throw new \RuntimeException(
                sprintf('Failed to create the DBAL connection proxy directory "%s".', $proxyDir)
            );
        }
    }
}
