<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Form\Type\Composer;

use Oro\Bundle\DistributionBundle\Form\Type\Composer\ConfigType;
use Oro\Bundle\DistributionBundle\Form\Type\Composer\RepositoryType;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ConfigTypeTest extends \PHPUnit\Framework\TestCase
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
            ->with('oauth', TextType::class, ['label' => 'Github OAuth', 'required' => false])
            ->will($this->returnValue($builder));

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'repositories',
                CollectionType::class,
                ['entry_type' => RepositoryType::class, 'allow_add' => true, 'allow_delete' => true]
            );

        $type->buildForm($builder, []);
    }
}
