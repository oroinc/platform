<?php

namespace Oro\Bundle\EmailBundle\Mailer\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport as TransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Mime\RawMessage;

/**
 * Makes transports be created when they are needed, i.e. when send() is called.
 */
class LazyTransports implements TransportInterface
{
    private TransportFactory $transportFactory;

    /**
     * @var array
     *  [
     *      'transport_name' => 'scheme://transport/dsn',
     *      // ...
     *  ]
     */
    private array $transportsDsns;

    private ?TransportInterface $transports = null;

    /**
     * @param TransportFactory $transportFactory
     * @param array $transportsDsns Transports DSNs
     *  [
     *      'transport_name' => 'scheme://transport/dsn',
     *      // ...
     *  ]
     */
    public function __construct(TransportFactory $transportFactory, array $transportsDsns)
    {
        $this->transportFactory = $transportFactory;
        $this->transportsDsns = $transportsDsns;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        return $this->getTransports()->send($message, $envelope);
    }

    public function __toString(): string
    {
        return '[' . implode(',', array_keys($this->transportsDsns)) . ']';
    }

    private function getTransports(): Transports
    {
        if (!$this->transports) {
            $this->transports = $this->transportFactory->fromStrings($this->transportsDsns);
        }

        return $this->transports;
    }
}
