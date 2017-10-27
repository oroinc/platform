<?php

namespace Oro\Bundle\SecurityTestBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractProductAttributeXssFixture extends AbstractEnumFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        $xssProvider = $this->container->get('oro_security_test.faker.provider.xss');
        $xssProvider->setPrefix('a');

        return [
            'test1' => $xssProvider->xss('Attribute.option'),
            'test2' => $xssProvider->xss('Attribute.option')
        ];
    }
}
