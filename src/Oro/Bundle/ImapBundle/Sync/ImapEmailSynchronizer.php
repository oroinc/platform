<?php

namespace Oro\Bundle\ImapBundle\Sync;

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
    /** @var string */
    protected static $messageQueueTopic = SyncEmailsTopic::NAME;

    /** @var ImapEmailSynchronizationProcessorFactory */
    protected $syncProcessorFactory;

    /** @var ImapConnectorFactory */
    protected $connectorFactory;

    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var OAuthManagerRegistry */
    protected $oauthManagerRegistry;

    /** @var SyncCredentialsIssueManager */
    private $credentialsIssueManager;

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

    public function setCredentialsManager(SyncCredentialsIssueManager $credentialsIssueManager)
    {
        $this->credentialsIssueManager = $credentialsIssueManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return ($origin instanceof UserEmailOrigin) && $this->isTypeSupported($origin->getAccountType());
    }

    protected function isTypeSupported(string $accountType): bool
    {
        return (AccountTypeModel::ACCOUNT_TYPE_OTHER === $accountType)
            || $this->oauthManagerRegistry->isOauthImapEnabled($accountType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEmailOriginClass()
    {
        return UserEmailOrigin::class;
    }

    /**
     * {@inheritdoc}
     */
    public function supportScheduleJob()
    {
        return true;
    }

    /**
     * Creates a processor is used to synchronize emails
     *
     * @param UserEmailOrigin $origin
     * @return ImapEmailSynchronizationProcessor
     */
    protected function createSynchronizationProcessor($origin)
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

    /**
     * {@inheritdoc}
     */
    protected function delegateToProcessor(
        EmailOrigin $origin,
        AbstractEmailSynchronizationProcessor $processor,
        SynchronizationProcessorSettings $settings = null
    ) {
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

    /**
     * {@inheritdoc}
     */
    protected function doSyncOrigin(EmailOrigin $origin, SynchronizationProcessorSettings $settings = null)
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
}
