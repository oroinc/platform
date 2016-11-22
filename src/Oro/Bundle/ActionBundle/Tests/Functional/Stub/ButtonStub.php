<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Stub;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonInterface;

class ButtonStub implements ButtonInterface
{
    const TITLE = 'Stub button title';
    const LABEL = 'Stub button label';

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
        return __DIR__ . '/button.html.twig';
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
}
