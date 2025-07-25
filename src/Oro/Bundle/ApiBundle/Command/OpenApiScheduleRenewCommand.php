<?php

namespace Oro\Bundle\ApiBundle\Command;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Async\Topic\CreateOpenApiSpecificationTopic;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Schedules renew all OpenAPI specifications.
 */
#[AsCommand(
    name: 'oro:api:doc:open-api:schedule-renew',
    description: 'Schedules renew all OpenAPI specifications.'
)]
class OpenApiScheduleRenewCommand extends Command
{
    private ManagerRegistry $doctrine;
    private MessageProducerInterface $producer;

    public function __construct(ManagerRegistry $doctrine, MessageProducerInterface $producer)
    {
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command schedules the renewal of all OpenAPI specifications.

  <info>php %command.full_name%</info>

HELP
            )
        ;

        parent::configure();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Scheduling renew of OpenAPI specifications...');

        $items = $this->getOpenApiSpecificationsToRenew();
        if ($items) {
            $this->updateOpenApiSpecificationStatuses();
            foreach ($items as $item) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $item['name']));
                $this->producer->send(
                    CreateOpenApiSpecificationTopic::getName(),
                    ['entityId' => $item['id'], 'renew' => true]
                );
            }
        }

        return self::SUCCESS;
    }

    private function getOpenApiSpecificationsToRenew(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository(OpenApiSpecification::class);

        return $repository->createQueryBuilder('e')
            ->select('e.id, e.name')
            ->where('e.status NOT IN(:renew_status, :creation_status)')
            ->setParameter('renew_status', OpenApiSpecification::STATUS_RENEWING)
            ->setParameter('creation_status', OpenApiSpecification::STATUS_CREATING)
            ->orderBy('e.updatedAt', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    private function updateOpenApiSpecificationStatuses(): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository(OpenApiSpecification::class);
        $repository->createQueryBuilder('e')
            ->update(OpenApiSpecification::class, 'e')
            ->set('e.status', ':renew_status')
            ->where('e.status <> :renew_status AND e.status <> :creation_status')
            ->setParameter('renew_status', OpenApiSpecification::STATUS_RENEWING)
            ->setParameter('creation_status', OpenApiSpecification::STATUS_CREATING)
            ->getQuery()
            ->execute();
    }
}
