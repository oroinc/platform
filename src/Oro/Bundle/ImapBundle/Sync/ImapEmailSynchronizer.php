<?php

namespace Oro\Bundle\ImapBundle\Sync;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailsTopic;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Exception\InvalidCredentialsException;
use Oro\Bundle\ImapBundle\Exception\RefreshOAuthAccessTokenFailureException;
use Oro\Bundle\ImapBundle\Exception\SocketTimeoutException;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\OAuth2ConnectException;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\SyncCredentialsIssueManager;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * This class provides ability to synchronize email with IMAP
 */
class ImapEmailSynchronizer extends AbstractEmailSynchronizer
{
    protected static string $messageQueueTopic = SyncEmailsTopic::NAME;
    protected ImapEmailSynchronizationProcessorFactory $syncProcessorFactory;
    protected ImapConnectorFactory $connectorFactory;
    protected SymmetricCrypterInterface $encryptor;
    protected OAuthManagerRegistry $oauthManagerRegistry;
    private SyncCredentialsIssueManager $credentialsIssueManager;

    public function __construct(
        ManagerRegistry $doctrine,
        KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory,
        ImapEmailSynchronizationProcessorFactory $syncProcessorFactory,
        ImapConnectorFactory $connectorFactory,
        SymmetricCrypterInterface $encryptor,
        OAuthManagerRegistry $oauthManagerRegistry,
        NotificationAlertManager $notificationAlertManager
    ) {
        parent::__construct($doctrine, $knownEmailAddressCheckerFactory, $notificationAlertManager);

        $this->syncProcessorFactory = $syncProcessorFactory;
        $this->connectorFactory     = $connectorFactory;
        $this->encryptor            = $encryptor;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    public function setCredentialsManager(SyncCredentialsIssueManager $credentialsIssueManager): void
    {
        $this->credentialsIssueManager = $credentialsIssueManager;
    }

    #[\Override]
    public function supports(EmailOrigin $origin): bool
    {
        return ($origin instanceof UserEmailOrigin) && $this->isTypeSupported($origin->getAccountType());
    }

    protected function isTypeSupported(string $accountType): bool
    {
        return (AccountTypeModel::ACCOUNT_TYPE_OTHER === $accountType)
            || $this->oauthManagerRegistry->isOauthImapEnabled($accountType);
    }

    #[\Override]
    protected function getEmailOriginClass(): string
    {
        return UserEmailOrigin::class;
    }

    #[\Override]
    public function supportScheduleJob(): bool
    {
        return true;
    }

    /**
     * Creates a processor is used to synchronize emails
     *
     * @param UserEmailOrigin $origin
     * @return ImapEmailSynchronizationProcessor
     */
    #[\Override]
    protected function createSynchronizationProcessor(object $origin): ImapEmailSynchronizationProcessor
    {
        $manager = $this->oauthManagerRegistry->hasManager($origin->getAccountType())
            ? $this->oauthManagerRegistry->getManager($origin->getAccountType())
            : null;

        try {
            $accessToken = $manager ? $manager->getAccessTokenWithCheckingExpiration($origin) : null;
        } catch (RefreshOAuthAccessTokenFailureException $e) {
            $notification = EmailSyncNotificationAlert::createForRefreshTokenFail($e->getMessage());
            $this->notificationsBag->addNotification($notification);

            throw $e;
        }

        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword()),
            $accessToken
        );

        return $this->syncProcessorFactory->create(
            new ImapEmailManager($this->connectorFactory->createImapConnector($config)),
            $this->getKnownEmailAddressChecker()
        );
    }

    #[\Override]
    protected function delegateToProcessor(
        EmailOrigin $origin,
        AbstractEmailSynchronizationProcessor $processor,
        ?SynchronizationProcessorSettings $settings = null
    ): void {
        try {
            parent::delegateToProcessor($origin, $processor, $settings);
        } catch (SocketTimeoutException $ex) {
            $this->logger->warning(
                sprintf(
                    'Exit because of "%s" origin\'s socket timed out. Error: "%s"',
                    $origin->getId(),
                    $ex->getMessage()
                ),
                $ex->getSocketMetadata()
            );
            $this->changeOriginSyncState($origin, self::SYNC_CODE_SUCCESS);

            return;
        }
    }

    #[\Override]
    protected function doSyncOrigin(EmailOrigin $origin, ?SynchronizationProcessorSettings $settings = null): void
    {
        try {
            parent::doSyncOrigin($origin, $settings);
        } catch (InvalidCredentialsException $ex) {
            // save information of invalid origin
            $this->credentialsIssueManager->addInvalidOrigin($origin);

            throw $ex;
        } catch (OAuth2ConnectException $ex) {
            // save information of invalid origin
            $this->credentialsIssueManager->addInvalidOrigin($origin);

            throw $ex;
        } catch (\Exception $ex) {
            throw $ex;
        }

        // remove success processed origin
        $this->credentialsIssueManager->removeOriginFromTheFailed($origin);
    }

    #[\Override]
    protected function findOriginToSyncQueryBuilder(int $maxConcurrentTasks, int $minExecIntervalInMin): QueryBuilder
    {
        $queryBuilder = parent::findOriginToSyncQueryBuilder($maxConcurrentTasks, $minExecIntervalInMin);

        return $this->addCredentialFilter($queryBuilder);
    }

    #[\Override]
    protected function findOriginQueryBuilder(int $originId): QueryBuilder
    {
        $queryBuilder = parent::findOriginQueryBuilder($originId);

        return $this->addCredentialFilter($queryBuilder);
    }

    private function addCredentialFilter(QueryBuilder $queryBuilder): QueryBuilder
    {
        $queryBuilder
            ->andWhere('o.imapHost IS NOT NULL')
            ->andWhere('o.imapPort > 0')
            ->andWhere('o.user IS NOT NULL')
            ->andWhere('o.password IS NOT NULL');

        return $queryBuilder;
    }
}
