<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ImapEmailBodyLoader implements EmailBodyLoaderInterface
{
    /**
     * @var ImapConnectorFactory
     */
    protected $connectorFactory;

    /** @var Mcrypt */
    protected $encryptor;

    /** @var ImapEmailGoogleOauth2Manager */
    protected $imapEmailGoogleOauth2Manager;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ImapConnectorFactory $connectorFactory
     * @param Mcrypt $encryptor
     * @param ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
     * @param ConfigManager $configManager
     */
    public function __construct(
        ImapConnectorFactory $connectorFactory,
        Mcrypt $encryptor,
        ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager,
        ConfigManager $configManager
    ) {
        $this->connectorFactory = $connectorFactory;
        $this->encryptor = $encryptor;
        $this->imapEmailGoogleOauth2Manager = $imapEmailGoogleOauth2Manager;
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

        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword()),
            $this->imapEmailGoogleOauth2Manager->getAccessTokenWithCheckingExpiration($origin)
        );

        $manager = new ImapEmailManager($this->connectorFactory->createImapConnector($config));
        $manager->selectFolder($folder->getFullName());

        $repo = $em->getRepository('OroImapBundle:ImapEmail');
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
