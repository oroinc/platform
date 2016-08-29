<?php

namespace Oro\Bundle\EmailBundle\Command;

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

class PurgeEmailAttachmentCommand extends ContainerAwareCommand
{
    const NAME = 'oro:email-attachment:purge';

    const OPTION_SIZE = 'size';
    const OPTION_ALL  = 'all';

    const LIMIT = 100;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::NAME)
            ->setDescription('Purges emails attachments')
            ->addOption(
                static::OPTION_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Purges emails attachments larger that option size in MB. Default to system configuration value.'
            )
            ->addOption(
                static::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Purges all emails attachments ignoring "size" option.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $size = $this->getSize($input);

        $emailAttachments = $this->getEmailAttachments($size);

        $count = count($emailAttachments);
        if ($count) {
            $em = $this->getEntityManager();
            $progress = new ProgressBar($output, $count);
            $progress->setFormat('debug');

            $progress->start();
            foreach ($emailAttachments as $attachment) {
                $this->removeAttachment($em, $attachment, $size);
                $progress->advance();
            }
            $progress->finish();
        } else {
            $output->writeln('No emails attachments to purify.');
        }
    }

    /**
     * Returns size in bytes
     *
     * @param InputInterface $input
     *
     * @return int
     */
    protected function getSize(InputInterface $input)
    {
        $all  = $input->getOption(static::OPTION_ALL);
        $size = $input->getOption(static::OPTION_SIZE);

        if ($all) {
            return 0;
        }

        if ($size === null) {
            $size = $this->getConfigManager()->get('oro_email.attachment_sync_max_size');
        }

        /** Convert Megabytes to Bytes */
        return (int)$size * pow(10, 6);
    }

    /**
     * @param EntityManager   $em
     * @param EmailAttachment $attachment
     * @param int             $size
     */
    protected function removeAttachment(EntityManager $em, EmailAttachment $attachment, $size)
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
    protected function getEmailAttachments($size)
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
    protected function createEmailAttachmentQb($size)
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
