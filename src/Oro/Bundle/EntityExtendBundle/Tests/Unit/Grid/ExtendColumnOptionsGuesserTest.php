<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Grid\ExtendColumnOptionsGuesser;
use Symfony\Component\Form\Guess\Guess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendColumnOptionsGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ExtendColumnOptionsGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->guesser = new ExtendColumnOptionsGuesser($this->configManager);
    }

    public function testGuessFormatterNoGuess()
    {
        $guess = $this->guesser->guessFormatter('TestClass', 'testProp', 'string');
        $this->assertNull($guess);
    }

    public function testGuessFilterNoGuess()
    {
        $guess = $this->guesser->guessFilter('TestClass', 'testProp', 'string');
        $this->assertNull($guess);
    }

    public function testGuessFormatterForEnumNoConfig()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(false);

        $guess = $this->guesser->guessFormatter($class, $property, 'enum');
        $this->assertNull($guess);
    }

    public function testGuessFilterForEnumNoConfig()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(false);

        $guess = $this->guesser->guessFilter($class, $property, 'enum');
        $this->assertNull($guess);
    }

    public function testGuessFormatterForMultiEnumNoConfig()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(false);

        $guess = $this->guesser->guessFormatter($class, $property, 'multiEnum');
        $this->assertNull($guess);
    }

    public function testGuessFilterForMultiEnumNoConfig()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(false);

        $guess = $this->guesser->guessFilter($class, $property, 'multiEnum');
        $this->assertNull($guess);
    }

    public function testGuessFormatterForEnum()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $config = new Config(new FieldConfigId('extend', $class, $property, 'enum'));
        $config->set('target_entity', 'Test\EnumValue');

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);

        $guess = $this->guesser->guessFormatter($class, $property, 'enum');
        $this->assertEquals(
            [
                'frontend_type' => Property::TYPE_HTML,
                'type'          => 'twig',
                'template'      => '@OroEntityExtend/Datagrid/Property/enum.html.twig',
                'context'       => [
                    'entity_class' => $config->get('target_entity')
                ]
            ],
            $guess->getOptions()
        );
        $this->assertEquals(Guess::MEDIUM_CONFIDENCE, $guess->getConfidence());
    }

    public function testGuessSorterForEnum()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $guess = $this->guesser->guessSorter($class, $property, 'enum');
        $this->assertNull($guess);
    }

    public function testGuessFilterForEnum()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $config = new Config(new FieldConfigId('extend', $class, $property, 'enum'));
        $config->set('target_entity', 'Test\EnumValue');

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);

        $guess = $this->guesser->guessFilter($class, $property, 'enum');
        $this->assertEquals(
            [
                'type'       => 'enum',
                'null_value' => ':empty:',
                'class'      => 'Test\EnumValue'
            ],
            $guess->getOptions()
        );
        $this->assertEquals(Guess::MEDIUM_CONFIDENCE, $guess->getConfidence());
    }

    public function testGuessFormatterForMultiEnum()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $config = new Config(new FieldConfigId('extend', $class, $property, 'enum'));
        $config->set('target_entity', 'Test\EnumValue');

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);

        $guess = $this->guesser->guessFormatter($class, $property, 'multiEnum');
        $this->assertEquals(
            [
                'frontend_type' => Property::TYPE_HTML,
                'export_type'   => 'list',
                'type'          => 'twig',
                'template'      => '@OroEntityExtend/Datagrid/Property/multiEnum.html.twig',
                'context'       => [
                    'entity_class' => 'Test\EnumValue'
                ]
            ],
            $guess->getOptions()
        );
        $this->assertEquals(Guess::MEDIUM_CONFIDENCE, $guess->getConfidence());
    }

    public function testGuessSorterForMultiEnum()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $guess = $this->guesser->guessSorter($class, $property, 'multiEnum');
        $this->assertEquals(
            [
                'disabled' => true
            ],
            $guess->getOptions()
        );
        $this->assertEquals(Guess::MEDIUM_CONFIDENCE, $guess->getConfidence());
    }

    public function testGuessFilterForMultiEnum()
    {
        $class = 'TestClass';
        $property = 'testProp';

        $config = new Config(new FieldConfigId('extend', $class, $property, 'enum'));
        $config->set('target_entity', 'Test\EnumValue');

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);

        $guess = $this->guesser->guessFilter($class, $property, 'multiEnum');
        $this->assertEquals(
            [
                'type'       => 'multi_enum',
                'null_value' => ':empty:',
                'class'      => 'Test\EnumValue'
            ],
            $guess->getOptions()
        );
        $this->assertEquals(Guess::MEDIUM_CONFIDENCE, $guess->getConfidence());
    }
}
