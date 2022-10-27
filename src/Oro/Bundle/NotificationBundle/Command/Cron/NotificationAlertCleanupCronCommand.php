<?php

namespace Oro\Bundle\NotificationBundle\Command\Cron;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes resolved notification alerts that is older than 30 days.
 */
class NotificationAlertCleanupCronCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    public const DEFAULT_OUTDATED_ALERT_INTERVAL =  '30 days';

    /** @var string */
    protected static $defaultName = 'oro:cron:notification:alerts:cleanup';

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
    }

    protected function configure(): void
    {
        $this->setDescription('Deletes resolved notification alerts that is older than 30 days.');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return  '0 0 * * */0' ; // Every Sunday at 00:00
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityRepository $entityRepository */
        $entityRepository = $this->doctrine->getRepository(NotificationAlert::class);

        $outdatedInterval = new \DateTime('now', new \DateTimeZone('UTC'));
        $outdatedInterval->sub(\DateInterval::createFromDateString(self::DEFAULT_OUTDATED_ALERT_INTERVAL));

        $deletedCount = $entityRepository
            ->createQueryBuilder('a')
            ->where('a.resolved = :resolved')
            ->setParameter('resolved', true)
            ->andWhere('a.createdAt <= :datetime')
            ->setParameter('datetime', $outdatedInterval)
            ->delete()
            ->getQuery()
            ->execute();

        $symfonyStyle = new SymfonyStyle($input, $output);
        if ($deletedCount) {
            $symfonyStyle->success(sprintf(
                '%d outdated notification alert(s) was successfully deleted.',
                $deletedCount
            ));
        } else {
            $symfonyStyle->note('There are no outdated notification alerts.');
        }

        return 0;
    }
}
