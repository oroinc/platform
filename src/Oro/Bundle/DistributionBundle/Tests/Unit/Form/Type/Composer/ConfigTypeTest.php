<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Form\Type\Composer;


use Oro\Bundle\DistributionBundle\Form\Type\Composer\ConfigType;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;

class ConfigTypeTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionHelperTrait;
    use MockHelperTrait;

    /**
     * @test
     */
    public function shouldBeSubclassOfAbstractType()
    {
        $this->assertSubclassOf(
            'Symfony\Component\Form\AbstractType',
            'Oro\Bundle\DistributionBundle\Form\Type\Composer\ConfigType'
        );
    }

    /**
     * @test
     */
    public function shouldReturnName()
    {
        $type = new ConfigType();

        $this->assertEquals('oro_composer_config', $type->getName());
    }

    /**
     * @test
     */
    public function shouldBuildForm()
    {
        $type = new ConfigType();

        $builder = $this->createConstructorLessMock('Symfony\Component\Form\FormBuilder');
        $builder->expects($this->at(0))
            ->method('add')
            ->with('oauth', 'text', ['label' => 'Github OAuth', 'required' => false])
            ->will($this->returnValue($builder));

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'repositories',
                'collection',
                ['type' => 'oro_composer_repository', 'allow_add' => true, 'allow_delete' => true]
            );

        $type->buildForm($builder, []);
    }
}
 