<?php

namespace Oro\Bundle\BatchBundle\EventListener;

use Oro\Bundle\BatchBundle\Event\EventInterface;
use Oro\Bundle\BatchBundle\Event\InvalidItemEvent;
use Oro\Bundle\BatchBundle\Event\JobExecutionEvent;
use Oro\Bundle\BatchBundle\Event\StepExecutionEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subscribes to job execution events to log them.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoggerSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private string $translationLocale = 'en';

    private string $translationDomain = 'messages';

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EventInterface::BEFORE_JOB_EXECUTION => 'beforeJobExecution',
            EventInterface::JOB_EXECUTION_STOPPED => 'jobExecutionStopped',
            EventInterface::JOB_EXECUTION_INTERRUPTED => 'jobExecutionInterrupted',
            EventInterface::JOB_EXECUTION_FATAL_ERROR => 'jobExecutionFatalError',
            EventInterface::BEFORE_JOB_STATUS_UPGRADE => 'beforeJobStatusUpgrade',
            EventInterface::BEFORE_STEP_EXECUTION => 'beforeStepExecution',
            EventInterface::STEP_EXECUTION_SUCCEEDED => 'stepExecutionSucceeded',
            EventInterface::STEP_EXECUTION_INTERRUPTED => 'stepExecutionInterrupted',
            EventInterface::STEP_EXECUTION_ERRORED => 'stepExecutionErrored',
            EventInterface::STEP_EXECUTION_COMPLETED => 'stepExecutionCompleted',
            EventInterface::INVALID_ITEM => 'invalidItem',
        ];
    }

    /**
     * Set the translation locale
     */
    public function setTranslationLocale(string $translationLocale): void
    {
        $this->translationLocale = $translationLocale;
    }

    /**
     * Set the translation domain
     */
    public function setTranslationDomain(string $translationDomain): void
    {
        $this->translationDomain = $translationDomain;
    }

    /**
     * Log the job execution before the job execution
     */
    public function beforeJobExecution(JobExecutionEvent $event): void
    {
        $jobExecution = $event->getJobExecution();

        $this->logger->debug(sprintf('Job execution starting: %s', $jobExecution));
    }

    /**
     * Log the job execution when the job execution stopped
     */
    public function jobExecutionStopped(JobExecutionEvent $event): void
    {
        $jobExecution = $event->getJobExecution();

        $this->logger->debug(sprintf('Job execution was stopped: %s', $jobExecution));
    }

    /**
     * Log the job execution when the job execution was interrupted
     */
    public function jobExecutionInterrupted(JobExecutionEvent $event): void
    {
        $jobExecution = $event->getJobExecution();

        $this->logger->info(sprintf('Encountered interruption executing job: %s', $jobExecution));
        $this->logger->debug('Full exception', ['exception', $jobExecution->getFailureExceptions()]);
    }

    /**
     * Log the job execution when a fatal error was raised during job execution
     */
    public function jobExecutionFatalError(JobExecutionEvent $event): void
    {
        $jobExecution = $event->getJobExecution();

        $this->logger->error(
            'Encountered fatal error executing job',
            ['exception', $jobExecution->getFailureExceptions()]
        );
    }

    /**
     * Log the job execution before its status is upgraded
     */
    public function beforeJobStatusUpgrade(JobExecutionEvent $event): void
    {
        $jobExecution = $event->getJobExecution();

        $this->logger->debug(sprintf('Upgrading JobExecution status: %s', $jobExecution));
    }

    /**
     * Log the step execution before the step execution
     */
    public function beforeStepExecution(StepExecutionEvent $event): void
    {
        $stepExecution = $event->getStepExecution();

        $this->logger->info(sprintf('Step execution starting: %s', $stepExecution));
    }

    /**
     * Log the step execution when the step execution succeeded
     */
    public function stepExecutionSucceeded(StepExecutionEvent $event): void
    {
        $stepExecution = $event->getStepExecution();

        $this->logger->debug(sprintf('Step execution success: id= %d', $stepExecution->getId()));
    }

    /**
     * Log the step execution when the step execution was interrupted
     */
    public function stepExecutionInterrupted(StepExecutionEvent $event): void
    {
        $stepExecution = $event->getStepExecution();

        $this->logger->info(
            sprintf('Encountered interruption executing step: %s', $stepExecution->getFailureExceptionMessages())
        );
        $this->logger->debug('Full exception', ['exception', $stepExecution->getFailureExceptions()]);
    }

    /**
     * Log the step execution when the step execution was errored
     */
    public function stepExecutionErrored(StepExecutionEvent $event): void
    {
        $stepExecution = $event->getStepExecution();

        $this->logger->error(
            sprintf(
                'Encountered an error executing the step: %s',
                implode(
                    ', ',
                    array_map(
                        function ($exception) {
                            return $this->translator->trans(
                                $exception['message'],
                                $exception['messageParameters'],
                                $this->translationDomain,
                                $this->translationLocale
                            );
                        },
                        $stepExecution->getFailureExceptions()
                    )
                )
            )
        );
    }

    /**
     * Log the step execution when the step execution was completed
     */
    public function stepExecutionCompleted(StepExecutionEvent $event): void
    {
        $stepExecution = $event->getStepExecution();

        $this->logger->debug(sprintf('Step execution complete: %s', $stepExecution));
    }

    /**
     * Log invalid item event
     */
    public function invalidItem(InvalidItemEvent $event): void
    {
        $this->logger->warning(
            sprintf(
                'The %s was unable to handle the following item: %s (REASON: %s)',
                $event->getClass(),
                $this->formatAsString($event->getItem()),
                $this->translator->trans(
                    $event->getReason(),
                    $event->getReasonParameters(),
                    $this->translationDomain,
                    $this->translationLocale
                )
            )
        );
    }

    /**
     * Format anything as a string
     *
     * @param mixed $data
     *
     * @return string
     */
    private function formatAsString($data): string
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[] = sprintf(
                    '%s => %s',
                    $this->formatAsString($key),
                    $this->formatAsString($value)
                );
            }

            return sprintf("[%s]", implode(', ', $result));
        }

        if (is_bool($data)) {
            return $data ? 'true' : 'false';
        }

        return (string)$data;
    }
}
