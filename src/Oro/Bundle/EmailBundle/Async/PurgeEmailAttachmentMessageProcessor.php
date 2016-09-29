<?php
namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PurgeEmailAttachmentMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const LIMIT = 100;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param RegistryInterface $doctrine
     * @param ConfigManager $configManager
     */
    public function __construct(RegistryInterface $doctrine, ConfigManager $configManager)
    {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $payload = JSON::decode($message->getBody());
        $payload = array_merge([
            'size' => null,
            'all' => false,
        ], $payload);

        $size = $this->getSize($payload);

        $emailAttachments = $this->getEmailAttachments($size);
        foreach ($emailAttachments as $attachment) {
            $this->removeAttachment($this->getEntityManager(), $attachment, $size);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PURGE_EMAIL_ATTACHMENT];
    }

    /**
     * Returns size in bytes
     *
     * @param array $payload
     *
     * @return int
     */
    private function getSize(array $payload)
    {
        $size = $payload['size'];

        if ($payload['all']) {
            return 0;
        }

        if ($size === null) {
            $size = $this->configManager->get('oro_email.attachment_sync_max_size');
        }

        /** Convert Megabytes to Bytes */
        return (int) $size * pow(10, 6);
    }

    /**
     * @param EntityManager   $em
     * @param EmailAttachment $attachment
     * @param int             $size
     */
    private function removeAttachment(EntityManager $em, EmailAttachment $attachment, $size)
    {
        // Double check of attachment size
        if ($size) {
            if ($attachment->getSize() < $size) {
                return;
            }
        }

        $em->remove($attachment);
    }

    /**
     * @param int $size
     *
     * @return BufferedQueryResultIterator
     */
    private function getEmailAttachments($size)
    {
        $qb = $this->createEmailAttachmentQb($size);
        $em = $this->getEntityManager();

        $emailAttachments = (new BufferedQueryResultIterator($qb))
            ->setBufferSize(static::LIMIT)
            ->setPageCallback(
                function () use ($em) {
                    $em->flush();
                    $em->clear();
                }
            );

        return $emailAttachments;
    }

    /**
     * @param int $size
     *
     * @return QueryBuilder
     */
    private function createEmailAttachmentQb($size)
    {
        $qb = $this->getEmailAttachmentRepository()
            ->createQueryBuilder('a')
            ->join('a.attachmentContent', 'attachment_content');

        if ($size > 0) {
            $qb
                ->andWhere(
                    <<<'DQL'
                    CASE WHEN attachment_content.contentTransferEncoding = 'base64' THEN
    (LENGTH(attachment_content.content) - LENGTH(attachment_content.content)/77) * 3 / 4 - 2
ELSE
    LENGTH(attachment_content.content)
END >= :size
DQL
                )
                ->setParameter('size', $size);
        }

        return $qb;
    }

    /**
     * @return EntityRepository
     */
    private function getEmailAttachmentRepository()
    {
        return $this->doctrine->getRepository(EmailAttachment::class);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(EmailAttachment::class);
    }
}
