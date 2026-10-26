<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;

/**
 * Keeps the entity and field that a new file is submitted to until the file is stored with its parent entity.
 */
class FileValidationTargetRegistry
{
    /** @var \WeakMap<File, FileValidationTarget> */
    private \WeakMap $targets;

    public function __construct()
    {
        $this->targets = new \WeakMap();
    }

    public function register(File $file, FileValidationTarget $target): void
    {
        $this->targets[$file] = $target;
    }

    public function get(File $file): ?FileValidationTarget
    {
        return $this->targets[$file] ?? null;
    }

    public function clear(): void
    {
        $this->targets = new \WeakMap();
    }
}
