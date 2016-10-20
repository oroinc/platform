<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\DBAL\Connection;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;

/**
 * Converts email body representations.
 * Will be deleted in 2.0
 */
class ConvertEmailBodyToTextBody extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:email:convert-body-to-text';

    const BATCH_SIZE = 500;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('Converts emails body. Generates and stores textual email body representation.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Conversion of emails body is started.</info>');

        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();

        $tableName = $this->queryHelper->getTableName('Oro\Bundle\EmailBundle\Entity\EmailBody');
        $selectQuery = 'select id, body from ' . $tableName . ' where body is not null and text_body is null '
            . 'order by created desc limit :limit offset :offset';
        $pageNumber = 0;
        $emailBodyHelper = new EmailBodyHelper();
        while (true) {
            $output->writeln(sprintf('<info>Process page %s.</info>', $pageNumber + 1));
            $data = $connection->fetchAll(
                $selectQuery,
                ['limit' => self::BATCH_SIZE, 'offset' => self::BATCH_SIZE * $pageNumber],
                ['limit' => 'integer', 'offset' => 'integer']
            );

            // exit if we have no data anymore
            if (count($data) === 0) {
                break;
            }

            foreach ($data as $dataArray) {
                $connection->update(
                    $tableName,
                    ['text_body' => $emailBodyHelper->getTrimmedClearText($dataArray['body'])],
                    ['id' => $dataArray['id']],
                    ['textBody' => 'string']
                );
            }

            $pageNumber++;
        }

        $output->writeln('<info>Job complete.</info>');
    }
}
