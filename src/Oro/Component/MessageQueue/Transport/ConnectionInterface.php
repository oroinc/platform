<?php
namespace Oro\Component\MessageQueue\Transport;

/**
 * A Connection object is a client's active connection to its MQ provider.
 *
 * Because the creation of a connection involves setting up authentication and communication,
 * a connection is a relatively heavyweight object.
 * Most clients will do all their messaging with a single connection.
 * Other more advanced applications may use several connections.
 *
 * A transport client typically creates a connection, one or more sessions,
 * and a number of message producers and consumers.
 *
 * @link https://docs.oracle.com/javaee/1.4/api/javax/MQ/Connection.html
 */
interface ConnectionInterface
{
    /**
     * Creates a Session object.
     *
     * @return SessionInterface
     */
    public function createSession();

    /**
     * Close connection
     *
     * @return void
     */
    public function close();
}
