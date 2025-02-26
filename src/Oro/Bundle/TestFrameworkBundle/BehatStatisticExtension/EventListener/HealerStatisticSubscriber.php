<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener;

use Doctrine\DBAL\Connection;
use Oro\Bundle\TestFrameworkBundle\Behat\Healer\Event\AfterHealerEvent;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\CriteriaArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Subscribe to Healer Event`s to collect healer statistics.
 */
class HealerStatisticSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Connection $connection,
        protected CriteriaArrayCollection $criteria,
        protected KernelInterface $kernel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterHealerEvent::class => ['afterHealer']
        ];
    }

    public function afterHealer(AfterHealerEvent $event): void
    {
        try {
            $statisticData = [
                'healing_id' => $event->getHealingId(),
                'path' => $event->getCall()->getFeature()->getFile(),
                'step' => $event->getCall()->getStep()->getText(),
                'healer' => $event->getHealer()::class,
                'time' => $event->getTime(),
                'healing_status' => (int)!$event->getCallResult()->hasException(),
            ];

            $this->pushStatistic($statisticData);
        } catch (\Exception $exception) {
            // prevent exception thrown during statistics collection
            $this->getLogger()->error($exception->getMessage());
        }
    }

    protected function pushStatistic(array $data): void
    {
        $this->connection->insert(
            'behat_healer_stat',
            array_merge(
                $data,
                [
                    'git_branch' => $this->criteria->get('branch_name') ?: $this->criteria->get('single_branch_name'),
                    'git_target' => $this->criteria->get('target_branch'),
                    'build_id' => $this->criteria->get('build_id'),
                ]
            )
        );
    }

    private function getLogger(): LoggerInterface
    {
        return $this->kernel->getContainer()->get('monolog.logger.consumer');
    }
}
