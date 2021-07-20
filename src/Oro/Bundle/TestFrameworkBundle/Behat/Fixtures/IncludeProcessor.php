<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Nelmio\Alice\IsAServiceTrait;
use Nelmio\Alice\Parser\IncludeProcessorInterface;
use Nelmio\Alice\ParserInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Resolves oro path names.
 */
class IncludeProcessor implements IncludeProcessorInterface
{
    use IsAServiceTrait;

    /** @var IncludeProcessorInterface */
    protected $includeProcessor;

    /** @var KernelInterface */
    protected $kernel;

    public function __construct(IncludeProcessorInterface $includeProcessor, KernelInterface $kernel)
    {
        $this->includeProcessor = $includeProcessor;
        $this->kernel = $kernel;
    }

    /**
     * @inheritdoc
     */
    public function process(ParserInterface $parser, string $file, array $data): array
    {
        $includes = $data['include'] ?? [];

        if ($includes) {
            foreach ($includes as $key => $include) {
                if (isset($include[0]) && '@' === $include[0]) {
                    $includes[$key] = $this->kernel->locateResource(
                        str_replace(':', '/Tests/Behat/Features/Fixtures/', $include)
                    );
                }
            }

            $data['include'] = $includes;
        }

        return $this->includeProcessor->process($parser, $file, $data);
    }
}
