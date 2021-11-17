<?php

namespace Oro\Bundle\ImapBundle\Mailer\Transport;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Mailer\Transport\ConfigureLocalDomainTrait;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Mailer transport that uses SMTP settings from UserEmailOrigin.
 */
class UserEmailOriginTransport implements TransportInterface
{
    use ConfigureLocalDomainTrait;

    public const HEADER_NAME = 'X-User-Email-Origin-Id';

    private Transport $transportFactory;

    private ManagerRegistry $managerRegistry;

    private DsnFromUserEmailOriginFactory $dsnFromUserEmailOriginFactory;

    private ?RequestStack $requestStack;

    private array $transports = [];

    public function __construct(
        Transport $transportFactory,
        ManagerRegistry $managerRegistry,
        DsnFromUserEmailOriginFactory $dsnFromUserEmailOriginFactory,
        ?RequestStack $requestStack = null
    ) {
        $this->transportFactory = $transportFactory;
        $this->managerRegistry = $managerRegistry;
        $this->dsnFromUserEmailOriginFactory = $dsnFromUserEmailOriginFactory;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if (!$message instanceof Message) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Message was expected to be an instance of "%s" at this point, got "%s"',
                    Message::class,
                    get_debug_type($message)
                )
            );
        }

        $userEmailOriginId = $this->getUserEmailOriginId($message);
        if (!isset($this->transports[$userEmailOriginId])) {
            $userEmailOrigin = $this->getUserEmailOrigin($userEmailOriginId);
            $dsn = $this->dsnFromUserEmailOriginFactory->create($userEmailOrigin);

            $this->transports[$userEmailOriginId] = $this->transportFactory->fromDsnObject($dsn);

            $this->configureLocalDomain($this->transports[$userEmailOriginId], $this->requestStack);
        }

        return $this->transports[$userEmailOriginId]->send($message, $envelope);
    }

    private function getUserEmailOriginId(Message $message): int
    {
        $headers = $message->getHeaders();
        $headerName = self::HEADER_NAME;
        $userEmailOriginId = $headers->get($headerName)?->getBody();

        if (empty($userEmailOriginId)) {
            throw new TransportException('Header X-User-Email-Origin-Id was expected to be set');
        }

        $headers->remove($headerName);

        if (!is_numeric($userEmailOriginId)) {
            throw new TransportException(
                sprintf(
                    'Header X-User-Email-Origin-Id was expected to contain numeric id, got "%s"',
                    $userEmailOriginId
                )
            );
        }

        return (int)$userEmailOriginId;
    }

    private function getUserEmailOrigin(int $userEmailOriginId)
    {
        $userEmailOrigin = $this->managerRegistry
            ->getManagerForClass(UserEmailOrigin::class)
            ->find(UserEmailOrigin::class, $userEmailOriginId);

        if (!$userEmailOrigin) {
            throw new TransportException(
                sprintf('UserEmailOrigin #"%d" is not found', $userEmailOriginId)
            );
        }

        if (!$userEmailOrigin->isSmtpConfigured()) {
            throw new TransportException(
                sprintf('UserEmailOrigin #"%d" was expected to have configured SMTP settings', $userEmailOriginId)
            );
        }

        return $userEmailOrigin;
    }

    public function __toString(): string
    {
        return '<transport based on user email origin>';
    }
}
