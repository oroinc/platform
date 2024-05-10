<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @method null markTestSkipped(string $message)
 */
trait CheckProcessorsTrait
{
    protected function checkProcessors(): void
    {
        $processorsFinder = new ProcessorHelper($this->getParameters());
        if (!$processorsFinder->librariesExists()) {
            $this->markTestSkipped(
                sprintf(
                    'Should be tested only with "%s" and "%s" libraries.',
                    ProcessorHelper::PNGQUANT,
                    ProcessorHelper::JPEGOPTIM
                )
            );
        }
    }

    protected function getParameters(): ParameterBag
    {
        return new ParameterBag([
            'liip_imagine.jpegoptim.binary' => ProcessorHelper::findBinary(ProcessorHelper::JPEGOPTIM),
            'liip_imagine.pngquant.binary' => ProcessorHelper::findBinary(ProcessorHelper::PNGQUANT)
        ]);
    }
}
