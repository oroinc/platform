<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Stub;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;

class ButtonStub implements ButtonInterface
{
    const TITLE = 'Stub button title';
    const LABEL = 'Stub button label';

    /** @var array */
    protected $buttonOptions;

    public function __construct(array $buttonOptions = [])
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
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '@OroActionBundle/Tests/Functional/Stub/button.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateData(array $customData = [])
    {
        return [
            'title' => self::TITLE,
            'label' => self::LABEL
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonContext()
    {
        return new ButtonContext();
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->buttonOptions['name'];
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->buttonOptions['label'];
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->buttonOptions['icon'];
    }

    /**
     * @return string|null
     */
    public function getTranslationDomain()
    {
        return $this->buttonOptions['translationDomain'];
    }
}
