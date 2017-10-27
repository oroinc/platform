<?php

namespace Oro\Bundle\SecurityTestBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class SecurityTestBundleInstaller implements
    Installation,
    ExtendExtensionAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $options = ['test1', 'test2'];
        $this->addProductAttribute($schema, 'size', $options);
        $this->addProductAttribute($schema, 'color', $options);
        $this->addProductAttribute($schema, 'material', $options);
    }

    /**
     * @param Schema $schema
     * @param string $attributeName
     */
    protected function addProductAttribute(Schema $schema, $attributeName, $optionsData)
    {
        $xssProvider = $this->container->get('oro_security_test.faker.provider.xss');
        $xssProvider->setPrefix('b');
        $options = new OroOptions();
        $options->set('enum', 'immutable_codes', $optionsData);

        $enumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_product',
            $attributeName,
            'enum_' . $attributeName,
            false,
            false,
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'attribute' => ['is_attribute' => true],
                'entity' => [
                    'label' => $xssProvider->xss('Attribute.label'),
                    'description' => $xssProvider->xss('Attribute.description')
                ]
            ]
        );
        $enumTable->addOption(OroOptions::KEY, $options);
    }
}
