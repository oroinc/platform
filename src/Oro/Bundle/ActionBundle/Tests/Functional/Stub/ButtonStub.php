<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Stub;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;

class ButtonStub implements ButtonInterface
{
    public const TITLE = 'Stub button title';
    public const LABEL = 'Stub button label';

    /** @var array */
    protected $buttonOptions;

    /** @var array */
    protected $datagridOptions;

    public function __construct(array $buttonOptions = [], array $datagridOptions = [])
    {
        $this->buttonOptions = array_replace_recursive(
            [
                'name' => 'name',
                'label' => 'Label',
                'icon' => 'icon',
                'translationDomain' => 'de_de'
            ],
            $buttonOptions
        );

        $this->datagridOptions = array_replace(['aria_label' => 'Label'], $datagridOptions);
    }

    #[\Override]
    public function getOrder()
    {
        return 0;
    }

    #[\Override]
    public function getTemplate()
    {
        return '@OroActionStub/button.html.twig';
    }

    #[\Override]
    public function getTemplateData(array $customData = [])
    {
        return [
            'title' => self::TITLE,
            'label' => self::LABEL
        ];
    }

    #[\Override]
    public function getButtonContext()
    {
        return new ButtonContext();
    }

    #[\Override]
    public function getGroup()
    {
        return null;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getName()
    {
        return $this->buttonOptions['name'];
    }

    #[\Override]
    public function getLabel(): string
    {
        return (string) $this->buttonOptions['label'];
    }

    #[\Override]
    public function getAriaLabel(): ?string
    {
        return $this->datagridOptions['aria_label'];
    }

    /**
     * @return string
     */
    #[\Override]
    public function getIcon()
    {
        return $this->buttonOptions['icon'];
    }

    /**
     * @return string|null
     */
    #[\Override]
    public function getTranslationDomain()
    {
        return $this->buttonOptions['translationDomain'];
    }
}
