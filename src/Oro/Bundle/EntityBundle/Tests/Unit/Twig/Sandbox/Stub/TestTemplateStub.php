<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox\Stub;

use Twig\Environment;
use Twig\Source;
use Twig\Template;

class TestTemplateStub extends Template
{
    /** @var string */
    private $name;

    /** @var string */
    private $template;

    /**
     * @param Environment $env
     * @param string $name
     * @param string $template
     */
    public function __construct(Environment $env, $name, $template)
    {
        parent::__construct($env);

        $this->name = $name;
        $this->template = $template;
    }

    #[\Override]
    public function getTemplateName(): string
    {
        return $this->name;
    }

    #[\Override]
    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        yield $this->template;
    }

    #[\Override]
    public function getDebugInfo(): array
    {
        return [];
    }

    #[\Override]
    public function getSourceContext(): Source
    {
        return new Source('stub', 'stub');
    }
}
