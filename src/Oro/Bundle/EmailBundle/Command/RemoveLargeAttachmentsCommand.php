<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;

class RemoveLargeAttachmentsCommand extends ContainerAwareCommand
{
    const NAME = 'oro:email:remove-large-attachments';

    const OPTION_SIZE = 'size';
    const OPTION_ALL = 'all';

    const LIMIT = 100;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::NAME)
            ->setDescription('Removes large attachments')
            ->addOption(
                static::OPTION_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Overrides size in MB at which attachment is considered to be large. Defaults to system config.'
            )
            ->addOption(
                static::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Remove all attachments ignoring "size" option'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $size = $input->getOption(static::OPTION_ALL) ? null : $this->getSize($input->getOption(static::OPTION_SIZE));
        $qb = $this->createEmailAttachmentQb($size);

        if (!$qb) {
            return;
        }

        $em = $this->getEntityManager();
        $emailAttachments = (new BufferedQueryResultIterator($qb))
            ->setBufferSize(static::LIMIT)
            ->setPageCallback(function () use ($em) {
                $em->flush();
                $em->clear();
            });

        $removeAttachmentCallback = $this->createRemoveAttachmentCallback($size);

        $progress = new ProgressBar($output, count($emailAttachments));
        $progress->start();
        foreach ($emailAttachments as $attachment) {
            call_user_func($removeAttachmentCallback, $attachment);
            $progress->advance();
        }
        $progress->finish();
    }

    /**
     * @param EmailAttachment $attachment
     * @param int|null $size
     *
     * @return callable
     */
    protected function createRemoveAttachmentCallback($size)
    {
        if ($size === null) {
            return function (EmailAttachment $attachment) {
                $attachment->getEmailBody()->removeAttachment($attachment);
            };
        }

        return function (EmailAttachment $attachment) use ($size) {
            $content = $attachment->getContent();
            $contentSize = $content->getContentTransferEncoding() === 'base64'
                ? strlen(base64_decode($content->getContent()))
                : strlen($content->getContent());

            if ($contentSize < $size) {
                return;
            }

            $attachment->getEmailBody()->removeAttachment($attachment);
        };
    }

    /**
     * @param int|null $size
     *
     * @return QueryBuilder|null
     */
    protected function createEmailAttachmentQb($size)
    {
        if ($size === 0) {
            return null;
        }

        $qb = $this->getEmailAttachmentRepository()
            ->createQueryBuilder('a')
            ->join('a.attachmentContent', 'eac');

        if ($size !== null) {
            /**
             * Base64-encoded data takes about 33% more space than the original data.
             * @see http://php.net/manual/en/function.base64-encode.php
             */
            $qb
                ->andWhere(<<<'DQL'
CASE WHEN eac.contentTransferEncoding = 'base64' THEN
    LENGTH(eac.content) * 0.67
ELSE
    LENGTH(eac.content)
END >= :size
DQL
                )
                ->setParameter('size', $size);
        }

        return $qb;
    }

    /**
     * @param int|null$sizeInMb
     *
     * @return int
     */
    protected function getSize($sizeInMb = null)
    {
        return $sizeInMb !== null
            ? (int) ($sizeInMb * 1024 * 1024)
            : (int) ($this->getConfigManager()->get('oro_email.attachment_sync_max_size') * 1024 * 1024);
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->getContainer()->get('oro_config.global');
    }

    /**
     * @return EntityRepository
     */
    protected function getEmailAttachmentRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroEmailBundle:EmailAttachment');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroEmailBundle:EmailAttachment');
    }
}
