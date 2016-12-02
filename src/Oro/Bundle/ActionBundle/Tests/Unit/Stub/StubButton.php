<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Stub;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonInterface;

class StubButton implements ButtonInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 0;
    }

    /** {@inheritdoc} */
    public function getTemplate()
    {
        return 'stub.template.';
    }

    /** {@inheritdoc} */
    public function getTemplateData(array $customData = [])
    {
        return [];
    }

    /** {@inheritdoc} */
    public function getButtonContext()
    {
        return new ButtonContext();
    }

    /** {@inheritdoc} */
    public function getGroup()
    {
        return '';
    }
}
