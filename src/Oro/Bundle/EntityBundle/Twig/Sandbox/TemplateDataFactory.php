<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * Creates {@see TemplateData} from template parameters.
 * Adds system variables to template parameters.
 */
class TemplateDataFactory
{
    private TemplateRendererConfigProviderInterface $templateRendererConfigProvider;

    private EntityVariableComputer $entityVariableComputer;

    private EntityDataAccessor $entityDataAccessor;

    private string $systemSection = 'system';

    private string $entitySection = 'entity';

    private string $computedSection = 'computed';

    public function __construct(
        TemplateRendererConfigProviderInterface $templateRendererConfigProvider,
        EntityVariableComputer $entityVariableComputer,
        EntityDataAccessor $entityDataAccessor
    ) {
        $this->templateRendererConfigProvider = $templateRendererConfigProvider;
        $this->entityVariableComputer = $entityVariableComputer;
        $this->entityDataAccessor = $entityDataAccessor;
    }

    public function setSystemSection(string $systemSection): void
    {
        $this->systemSection = $systemSection;
    }

    public function setEntitySection(string $entitySection): void
    {
        $this->entitySection = $entitySection;
    }

    public function setComputedSection(string $computedSection): void
    {
        $this->computedSection = $computedSection;
    }

    public function createTemplateData(array $templateParams): TemplateData
    {
        $templateParams[$this->systemSection] = $this->templateRendererConfigProvider->getSystemVariableValues();

        return new TemplateData(
            $templateParams,
            $this->entityVariableComputer,
            $this->entityDataAccessor,
            $this->systemSection,
            $this->entitySection,
            $this->computedSection
        );
    }
}
