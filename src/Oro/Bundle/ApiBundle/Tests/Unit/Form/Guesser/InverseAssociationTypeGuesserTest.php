<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Guesser;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\Guesser\InverseAssociationTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;

class InverseAssociationTypeGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var InverseAssociationTypeGuesser */
    protected $guesser;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->associationManager = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new InverseAssociationTypeGuesser($this->doctrineHelper, $this->associationManager);
    }

    public function testGuessTypeWithoutConfigAccessor()
    {
        $this->assertNull($this->guesser->guessType('Test\Class', 'testProperty'));
    }

    public function testGuessTypeWithoutConfig()
    {
        $configAccessor = $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface');
        $this->guesser->setConfigAccessor($configAccessor);
        $this->assertNull($this->guesser->guessType('Test\Class', 'testProperty'));
    }

    public function testGuessTypeWithConfigThatHasNoGivenField()
    {
        $entityConfig = new EntityDefinitionConfig();
        $configAccessor = $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface');
        $configAccessor->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);
        $this->guesser->setConfigAccessor($configAccessor);
        $this->assertNull($this->guesser->guessType('Test\Class', 'testProperty'));
    }

    public function testGuessTypeWithNonInverseAssociationField()
    {
        $entityConfig = new EntityDefinitionConfig();
        $fieldConfig = new EntityDefinitionFieldConfig();
        $fieldConfig->setDataType('entity');
        $entityConfig->addField('testField', $fieldConfig);

        $configAccessor = $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface');

        $configAccessor->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);
        $this->guesser->setConfigAccessor($configAccessor);

        $this->assertNull($this->guesser->guessType('Test\Class', 'testField'));
    }

    public function testGuessType()
    {
        $entityConfig = new EntityDefinitionConfig();
        $fieldConfig = new EntityDefinitionFieldConfig();
        $fieldConfig->setDataType('inverseAssociation:Test\Source:oneToMany:someKind');
        $fieldConfig->setCollapsed(true);

        $entityConfig->addField('testField', $fieldConfig);

        $configAccessor = $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface');
        
        $configAccessor->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);
        $this->guesser->setConfigAccessor($configAccessor);

        $this->associationManager->expects($this->once())
            ->method('getAssociationTargets')
            ->willReturn(
                [
                    'Test\AnotherTarget' => 'another_target',
                    'Test\Class' => 'test_target'
                ]
            );

        $result = $this->guesser->guessType('Test\Class', 'testField');

        $this->assertInstanceOf('Symfony\Component\Form\Guess\TypeGuess', $result);
        $this->assertEquals('oro_api_entity', $result->getType());
        /** @var AssociationMetadata $metadata */
        $metadata =  $result->getOptions()['metadata'];
        $this->assertEquals('Test\Source', $metadata->getTargetClassName());
        $this->assertEquals(['Test\Source'], $metadata->getAcceptableTargetClassNames());
        $this->assertEquals('manyToOne', $metadata->getAssociationType());
        $this->assertEquals(true, $metadata->isCollapsed());
        $this->assertEquals('testField', $metadata->getName());
        $this->assertEquals('test_target', $metadata->get('association-field'));
    }

    public function testGuessRequired()
    {
        $this->assertNull($this->guesser->guessRequired('Test\Class', 'testField'));
    }

    public function testGuessMaxLength()
    {
        $this->assertNull($this->guesser->guessMaxLength('Test\Class', 'testField'));
    }

    public function testGuessPattern()
    {
        $this->assertNull($this->guesser->guessPattern('Test\Class', 'testField'));
    }
}
