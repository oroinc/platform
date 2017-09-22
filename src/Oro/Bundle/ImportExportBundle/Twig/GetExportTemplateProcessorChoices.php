<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class GetExportTemplateProcessorChoices extends \Twig_Extension
{
    /**
     * @var ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @param ProcessorRegistry $processorRegistry
     */
    public function __construct(ProcessorRegistry $processorRegistry)
    {
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_export_template_processor_choices', [$this, 'getExportProcessorsChoices']),
        ];
    }

    /**
     * @param string $entityName
     *
     * @return array
     */
    public function getExportProcessorsChoices(string $entityName): array
    {
        $aliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            $entityName
        );

        $result = [];
        foreach ($aliases as $alias) {
            $result[$alias] = $this->generateProcessorLabel($alias);
        }

        return $result;
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    protected function generateProcessorLabel(string $alias): string
    {
        return sprintf('oro.importexport.export_template.%s', $alias);
    }
}
