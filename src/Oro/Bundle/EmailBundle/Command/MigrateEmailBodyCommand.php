<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\DBAL\Connection;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;

class MigrateEmailBodyCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:email:migrate-email-body';

    const BATCH_SIZE = 500;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Migrates email body');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Start convert body.</info>');

        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $selectQuery = 'select id, body from oro_email_body where body_is_text = true and body is not null '
          . 'order by created desc limit :limit offset :offset';
        $updateQuery = 'update oro_email_body set text_body = :textBody where id = :id';
        $pageNumber = 0;
        $emailBodyHelper = new EmailBodyHelper();
        while (true) {
            $output->writeln(sprintf('<info>Process page %s.</info>', $pageNumber + 1));
            $data = $connection->executeQuery(
                $selectQuery,
                ['limit' => self::BATCH_SIZE, 'offset' => self::BATCH_SIZE * $pageNumber],
                ['limit' => 'integer', 'offset' => 'integer']
            )->fetchAll();

            // exit if we have no data anymore
            if (count($data) === 0) {
                break;
            }

            foreach ($data as $dataArray) {
                $output->writeln(sprintf('<info> --- %s</info>', $dataArray['id']));

                $connection->executeQuery(
                    $updateQuery,
                    ['id' => $dataArray['id'], 'textBody' => $emailBodyHelper->getClearBody($dataArray['body'])],
                    ['id' => 'integer', 'textBody' => 'text']
                );
            }

            $pageNumber++;
        }

        $output->writeln('<info>Job complete.</info>');
    }
}