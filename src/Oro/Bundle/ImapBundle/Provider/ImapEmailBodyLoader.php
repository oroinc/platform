<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Doctrine\ORM\EntityManager;
use Laminas\Mail\Protocol\Exception\RuntimeException;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Exception\SyncWithNotificationAlertException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\UnselectableFolderException;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * This class provides ability to load email body
 */
class ImapEmailBodyLoader implements EmailBodyLoaderInterface
{
    /** @var ImapConnectorFactory */
    protected $connectorFactory;

    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var OAuthManagerRegistry */
    protected $oauthManagerRegistry;

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(
        ImapConnectorFactory $connectorFactory,
        SymmetricCrypterInterface $encryptor,
        OAuthManagerRegistry $oauthManagerRegistry,
        ConfigManager $configManager
    ) {
        $this->connectorFactory = $connectorFactory;
        $this->encryptor = $encryptor;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof UserEmailOrigin;
    }

    /**
     * {@inheritdoc}
     */
    public function loadEmailBody(EmailFolder $folder, Email $email, EntityManager $em)
    {
        /** @var UserEmailOrigin $origin */
        $origin = $folder->getOrigin();
        $manager = $this->oauthManagerRegistry->hasManager($origin->getAccountType())
            ? $this->oauthManagerRegistry->getManager($origin->getAccountType())
            : null;

        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword()),
            $manager ? $manager->getAccessTokenWithCheckingExpiration($origin) : null
        );

        try {
            $manager = new ImapEmailManager($this->connectorFactory->createImapConnector($config));
            $manager->selectFolder($folder->getFullName());
        } catch (UnselectableFolderException $e) {
            throw new SyncWithNotificationAlertException(
                EmailSyncNotificationAlert::createForSwitchFolderFail(
                    sprintf('The folder "%s" cannot be selected.', $folder->getFullName()),
                ),
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (RuntimeException $e) {
            throw new SyncWithNotificationAlertException(
                EmailSyncNotificationAlert::createForSwitchFolderFail(
                    'Cannot connect to the IMAP server. Exception message:' . $e->getMessage()
                ),
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        $repo = $em->getRepository(ImapEmail::class);
        $query = $repo->createQueryBuilder('e')
            ->select('e.uid')
            ->innerJoin('e.imapFolder', 'if')
            ->where('e.email = ?1 AND if.folder = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, $folder)
            ->getQuery();

        $loadedEmail = $manager->findEmail($query->getSingleScalarResult());
        if (null === $loadedEmail) {
            throw new EmailBodyNotFoundException($email);
        }

        $builder = new EmailBodyBuilder($this->configManager);
        $builder->setEmailBody(
            $loadedEmail->getBody()->getContent(),
            $loadedEmail->getBody()->getBodyIsText()
        );
        foreach ($loadedEmail->getAttachments() as $attachment) {
            $builder->addEmailAttachment(
                $attachment->getFileName(),
                $attachment->getContent(),
                $attachment->getContentType(),
                $attachment->getContentTransferEncoding(),
                $attachment->getContentId(),
                $attachment->getFileSize()
            );
        }

        return $builder->getEmailBody();
    }
}
