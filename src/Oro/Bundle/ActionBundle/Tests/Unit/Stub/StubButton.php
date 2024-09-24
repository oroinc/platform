<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Stub;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;

class StubButton implements ButtonInterface
{
    /** @var int */
    private $order = 1;

    /** @var string */
    private $template = 'stub.template.';

    /** @var array */
    private $templateData = [];

    /** @var ButtonContext|null */
    private $buttonContext;

    /** @var string */
    private $group = '';

    /** @var string */
    private $label = 'stub.button.label';

    /** @var string */
    private $ariaLabel = 'stub.button.aria_label';

    /** @var string */
    private $name = 'stub.button.name';

    /** @var string */
    private $icon = 'stub.button.ico';

    /**
     * @param array $properties list of properties and values to assign to button
     */
    public function __construct(array $properties = [])
    {
        foreach ($properties as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    #[\Override]
    public function getOrder()
    {
        return $this->order;
    }

    #[\Override]
    public function getTemplate()
    {
        return $this->template;
    }

    #[\Override]
    public function getTemplateData(array $customData = [])
    {
        return $this->templateData;
    }

    #[\Override]
    public function getButtonContext()
    {
        return $this->buttonContext ?: new ButtonContext();
    }

    #[\Override]
    public function getGroup()
    {
        return $this->group;
    }

    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    #[\Override]
    public function getLabel(): string
    {
        return (string) $this->label;
    }

    #[\Override]
    public function getAriaLabel(): ?string
    {
        return $this->ariaLabel;
    }

    #[\Override]
    public function getIcon()
    {
        return $this->icon;
    }

    #[\Override]
    public function getTranslationDomain()
    {
        return 'test_domain';
    }
}
