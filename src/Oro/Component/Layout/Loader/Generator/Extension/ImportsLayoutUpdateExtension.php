<?php

namespace Oro\Component\Layout\Loader\Generator\Extension;

use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ImportsLayoutUpdateExtension implements ConfigLayoutUpdateGeneratorExtensionInterface
{
    const NODE_CONDITIONS = 'imports';

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $visitorCollection)
    {
        $source = $data->getSource();

        if (is_array($source) && !empty($source[self::NODE_CONDITIONS])) {
            foreach ($source[static::NODE_CONDITIONS] as $import) {
                if (strpos($import, '@') !== false) {
                    $import = $this->kernel->locateResource($import);
                }
                $visitorCollection->append(new ImportsLayoutUpdateVisitor($import));
            }
        }
    }
}
