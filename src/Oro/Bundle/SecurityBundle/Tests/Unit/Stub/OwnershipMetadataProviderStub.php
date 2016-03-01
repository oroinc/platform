<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Stub;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class OwnershipMetadataProviderStub extends OwnershipMetadataProvider
{
    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     *
     * @param array $classes
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase, array $classes = [])
    {
        $configProvider = $testCase->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $entityClassResolver = $testCase->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $entityClassResolver->expects($testCase->any())->method('getEntityClass')->willReturnArgument(0);

        $container = $testCase->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($testCase->any())
            ->method('get')
            ->will(
                $testCase->returnValueMap(
                    [
                        [
                            'oro_entity_config.provider.ownership',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $configProvider,
                        ],
                        [
                            'oro_entity.orm.entity_class_resolver',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $entityClassResolver,
                        ],
                    ]
                )
            );

        parent::__construct(
            array_merge(
                [
                    'organization' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization',
                    'business_unit' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit',
                    'user' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User',
                ],
                $classes
            )
        );
        $this->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($className)
    {
        return isset($this->metadata[$className])
            ? $this->metadata[$className]
            : parent::getMetadata($className);
    }

    /**
     * @param string $className
     * @param OwnershipMetadata $metadata
     */
    public function setMetadata($className, OwnershipMetadata $metadata)
    {
        $this->metadata[$className] = $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxAccessLevel($accessLevel, $className = null)
    {
        return $accessLevel;
    }
}
