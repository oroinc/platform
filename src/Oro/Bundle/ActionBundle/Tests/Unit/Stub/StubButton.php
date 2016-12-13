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

    /** {@inheritdoc} */
    public function getOrder()
    {
        return $this->order;
    }

    /** {@inheritdoc} */
    public function getTemplate()
    {
        return $this->template;
    }

    /** {@inheritdoc} */
    public function getTemplateData(array $customData = [])
    {
        return $this->templateData;
    }

    /** {@inheritdoc} */
    public function getButtonContext()
    {
        return $this->buttonContext ?: new ButtonContext();
    }

    /** {@inheritdoc} */
    public function getGroup()
    {
        return $this->group;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return $this->name;
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->label;
    }

    /** {@inheritdoc} */
    public function getIcon()
    {
        return $this->icon;
    }

    /** {@inheritdoc} */
    public function getTranslationDomain()
    {
        return 'test_domain';
    }
}
