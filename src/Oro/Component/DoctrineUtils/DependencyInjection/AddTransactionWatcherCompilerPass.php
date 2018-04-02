<?php

namespace Oro\Component\DoctrineUtils\DependencyInjection;

use Doctrine\DBAL\Connection;
use Oro\Component\DoctrineUtils\DBAL\ChainTransactionWatcher;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherAwareInterface;
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
    const CONNECTION_PROXY_NAMESPACE = 'OroDoctrineConnection';
    const CONNECTION_PROXY_CLASS     = 'ConnectionProxy';

    /** @var string */
    private $transactionWatcherTag;

    /** @var string */
    private $connectionName;

    /**
     * @param string $transactionWatcherTag
     * @param string $connectionName
     */
    public function __construct(string $transactionWatcherTag, string $connectionName = '')
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
        $extectedConnectionClass = Connection::class;
        if ('test' === $container->getParameter('kernel.environment')) {
            $extectedConnectionClass = PersistentConnection::class;
        }
        $connectionWrapperClass = $options['wrapperClass'] ?? $extectedConnectionClass;
        if (!is_a($connectionWrapperClass, $extectedConnectionClass, true)) {
            throw new \RuntimeException(sprintf(
                'The DBAL connection "wrapperClass" "%s" has to be "%s" or its subtype.',
                $connectionWrapperClass,
                $extectedConnectionClass
            ));
        }

        // make sure that the connection proxy directory exists
        $proxyDir = $this->getProxyDir($container);
        $this->ensureProxyDirExists($proxyDir);

        // replace the connection class with the proxy
        $options['wrapperClass'] = $this->generateProxy($proxyDir, $connectionWrapperClass);
        $connectionDef->replaceArgument(0, $options);

        // create the chain watcher and inject it to the connection proxy
        $watcherServiceId = $this->createWatcher($container, $connectionName, $watchers);
        $connectionDef->addMethodCall(
            'setTransactionWatcher',
            [new Reference($watcherServiceId)]
        );
    }

    /**
     * Gets the root directory where the connection proxy should be stored.
     *
     * @param string $cacheDir
     *
     * @return string
     */
    public static function getConnectionProxyRootDir(string $cacheDir): string
    {
        return $cacheDir . DIRECTORY_SEPARATOR . 'oro_entities';
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
            $watchers = call_user_func_array('array_merge', $watchers);
        }

        return $watchers;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string
     *
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
     * @param ContainerBuilder $container
     * @param string           $connectionName
     *
     * @return Definition
     *
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
                'The transaction watcher for the DBAL connection "%s" is alredy exist.',
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
     *
     * @return string The connection proxy fully-qualified class name
     *
     * @throws \RuntimeException if the creation of the proxy file failed
     */
    private function generateProxy(string $proxyDir, string $connectionClass): string
    {
        if (strpos($connectionClass, '\\') !== 0) {
            $connectionClass = '\\' . $connectionClass;
        }

        $transactionWatcherInterface = '\\' . TransactionWatcherInterface::class;
        $transactionWatcherAwareInterface = '\\' . TransactionWatcherAwareInterface::class;
        $proxyNamespace = self::CONNECTION_PROXY_NAMESPACE;
        $proxyClass = self::CONNECTION_PROXY_CLASS . '_' . md5($connectionClass);
        $proxyFile = $proxyDir . DIRECTORY_SEPARATOR . $proxyClass . '.php';

        if (!is_file($proxyFile)) {
            $this->writeProxyFile(
                $proxyFile,
                <<<PHP
<?php
namespace $proxyNamespace;

class $proxyClass extends $connectionClass implements $transactionWatcherAwareInterface
{
    private \$transactionWatcher;

    public function setTransactionWatcher($transactionWatcherInterface \$transactionWatcher = null)
    {
        \$this->transactionWatcher = \$transactionWatcher;
    }
    public function beginTransaction()
    {
        parent::beginTransaction();
        // the nesting level equal to 1 means that the root transaction is started,
        // for nested transactions the nesting level will be greater that 1
        if (null !== \$this->transactionWatcher && \$this->getTransactionNestingLevel() === 1) {
            \$this->transactionWatcher->onTransactionStarted();
        }
    }
    public function commit()
    {
        parent::commit();
        // the nesting level equal to 0 means that the root transaction is commited,
        // for nested transactions the nesting level will be greater that 0
        if (null !== \$this->transactionWatcher && \$this->getTransactionNestingLevel() === 0) {
            \$this->transactionWatcher->onTransactionCommited();
        }
    }
    public function rollBack()
    {
        parent::rollBack();
        // the nesting level equal to 0 means that the root transaction is rolled back,
        // for nested transactions the nesting level will be greater that 0
        if (null !== \$this->transactionWatcher && \$this->getTransactionNestingLevel() === 0) {
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
     * @param string $file
     * @param string $content
     *
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

    /**
     * @param ContainerBuilder $container
     *
     * @return string
     */
    private function getProxyDir(ContainerBuilder $container): string
    {
        return
            self::getConnectionProxyRootDir($container->getParameter('kernel.cache_dir'))
            . DIRECTORY_SEPARATOR
            . self::CONNECTION_PROXY_NAMESPACE;
    }

    /**
     * @param string $proxyDir
     *
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
