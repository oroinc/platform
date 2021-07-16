<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

/**
 * File driver for Maintenance Mode check
 */
class FileDriver extends AbstractDriver implements DriverTtlInterface
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
     * Write ttl to lock file
     *
     * {@inheritdoc}
     */
    protected function createLock(): bool
    {
        $handle = fopen($this->filePath, 'w+');
        if (isset($this->options['ttl']) && (int)$this->options['ttl']) {
            fwrite($handle, time() + $this->options['ttl']);
        }

        return (bool) $handle;
    }

    /**
     * {@inheritdoc}
     */
    protected function createUnlock(): bool
    {
        return unlink($this->filePath);
    }

    /**
     * Return true if file exists even ttl was expired so maintenance mode must still be on
     *
     * {@inheritdoc}
     */
    public function isExists(): bool
    {
        return file_exists($this->filePath);
    }

    /**
     * Check if maintenance has ttl and if it is expired
     *
     * @return bool
     */
    public function isExpired()
    {
        if (!$this->hasTtl()) {
            return false;
        }
        $now = new \DateTime('now');
        $accessTime = date('Y-m-d H:i:s', filemtime($this->filePath));
        $accessTime = new \DateTime($accessTime);
        $accessTime->modify(sprintf('+%s seconds', $this->getTtl()));

        return ($accessTime < $now);
    }

    /**
     * {@inheritdoc}
     */
    public function setTtl($value): void
    {
        $this->options['ttl'] = $value;
        //in case if file already exists update it with the ttl
        if (file_exists($this->filePath)) {
            $handle = fopen($this->filePath, 'w+');
            fwrite($handle, time() + $this->options['ttl']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl(): int
    {
        return file_exists($this->filePath)
            ? (int) file_get_contents($this->filePath) - filemtime($this->filePath)
            : $this->options['ttl'];
    }

    /**
     * {@inheritdoc}
     */
    public function hasTtl(): bool
    {
        return file_exists($this->filePath) ?: isset($this->options['ttl']);
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
