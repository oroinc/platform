<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

/**
 * File driver for Maintenance Mode check
 */
class FileDriver extends AbstractDriver
{
    protected string $filePath;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (!isset($options['file_path'])) {
            throw new \InvalidArgumentException(
                'Option "file_path" must be defined if File Driver configuration is used'
            );
        }
        $this->filePath = $options['file_path'];
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function createLock(): bool
    {
        return (bool) fopen($this->filePath, 'w+');
    }

    /**
     * {@inheritdoc}
     */
    protected function createUnlock(): bool
    {
        return unlink($this->filePath);
    }

    /**
     * Return true if file exists
     *
     * {@inheritdoc}
     */
    public function isExists(): bool
    {
        return file_exists($this->filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageLock(bool $resultTest): string
    {
        $key = $resultTest ? 'oro.maintenance.success_lock_file' : 'oro.maintenance.not_success_lock';

        return $this->translator->trans($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageUnlock(bool $resultTest): string
    {
        $key = $resultTest ? 'oro.maintenance.success_unlock' : 'oro.maintenance.not_success_unlock';

        return $this->translator->trans($key);
    }
}
