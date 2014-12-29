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
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class ImapEmailBodyLoader implements EmailBodyLoaderInterface
{
    /**
     * @var ImapConnectorFactory
     */
    protected $connectorFactory;

    /** @var Mcrypt */
    protected $encryptor;

    /**
     * Constructor
     *
     * @param ImapConnectorFactory $connectorFactory
     * @param Mcrypt $encryptor
     */
    public function __construct(ImapConnectorFactory $connectorFactory, Mcrypt $encryptor)
    {
        $this->connectorFactory = $connectorFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof ImapEmailOrigin;
    }

    /**
     * {@inheritdoc}
     */
    public function loadEmailBody(EmailFolder $folder, Email $email, EntityManager $em)
    {
        /** @var ImapEmailOrigin $origin */
        $origin = $folder->getOrigin();

        $config = new ImapConfig(
            $origin->getHost(),
            $origin->getPort(),
            $origin->getSsl(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword())
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

        $builder = new EmailBodyBuilder();
        $builder->setEmailBody(
            $loadedEmail->getBody()->getContent(),
            $loadedEmail->getBody()->getBodyIsText()
        );
        foreach ($loadedEmail->getAttachments() as $attachment) {
            $builder->addEmailAttachment(
                $attachment->getFileName(),
                $attachment->getContent(),
                $attachment->getContentType(),
                $attachment->getContentTransferEncoding()
            );
        }

        return $builder->getEmailBody();
    }
}
