<?php
namespace Oro\Bundle\MessageQueueBundle\Consumption;

use Oro\Component\MessageQueue\Consumption\Exception\LogicException;

trait InterruptConsumptionExtensionTrait
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * Create file if not exist
     *
     * @param string $filePath
     *
     * @throws LogicException
     */
    protected function touch($filePath)
    {
        if (! file_exists($filePath)) {
            $directory = dirname($filePath);

            if (! @mkdir($directory, 0777, true) && ! is_dir($directory)) {
                throw new LogicException(
                    sprintf('[InterruptConsumptionExtension] Cannot create directory %s', $directory)
                );
            }

            touch($filePath);

            chmod($directory, 0777); // in some cases mkdir() cannot set passed mode parameter
            chmod($filePath, 0666);
        }
    }
}
