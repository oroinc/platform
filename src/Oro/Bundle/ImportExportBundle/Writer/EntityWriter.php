<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Import-Export database entity writer
 */
class EntityWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    const SKIP_CLEAR = 'writer_skip_clear';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityDetachFixer */
    protected $detachFixer;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var ContextRegistry */
    protected $contextRegistry;

    /** @var string */
    private $className;

    /** @var array */
    private $config;

    /** @var DatabaseExceptionHelper */
    private $databaseExceptionHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EntityDetachFixer $detachFixer
     * @param ContextRegistry $contextRegistry
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityDetachFixer $detachFixer,
        ContextRegistry $contextRegistry,
        DatabaseExceptionHelper $databaseExceptionHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->detachFixer = $detachFixer;
        $this->contextRegistry = $contextRegistry;
        $this->databaseExceptionHelper = $databaseExceptionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        try {
            $entityManager = $this->doctrineHelper->getEntityManager($this->getClassName($items));
            foreach ($items as $item) {
                $entityManager->persist($item);
                $this->detachFixer->fixEntityAssociationFields($item, 1);
            }
            $entityManager->flush();

            $configuration = $this->getConfig();

            if (empty($configuration[self::SKIP_CLEAR])) {
                $entityManager->clear();
            }
        } catch (\Exception $e) {
            $driverException = $this->databaseExceptionHelper->getDriverException($e);

            if ($driverException && $this->databaseExceptionHelper->isDeadlock($driverException)) {
                $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
                $context->setValue('deadlockDetected', true);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @param array $items
     * @return string
     */
    protected function getClassName(array $items)
    {
        if (!$this->className) {
            $config = $this->getConfig();

            if (array_key_exists('entityName', $config)) {
                $this->className = $config['entityName'];

                return $this->className;
            }
        }

        if (!$this->className && array_key_exists(0, $items)) {
            $this->className = ClassUtils::getRealClass($items[0]);

            return $this->className;
        }

        if (!$this->className) {
            throw new \RuntimeException('entityName not resolved');
        }

        return $this->className;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = $this->contextRegistry->getByStepExecution($this->stepExecution)->getConfiguration();
        }

        return $this->config;
    }
}
