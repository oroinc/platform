<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Form\Type\Composer;


use Oro\Bundle\DistributionBundle\Form\Type\Composer\RepositoryType;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;

class RepositoryTypeTest extends \PHPUnit_Framework_TestCase
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
            'Oro\Bundle\DistributionBundle\Form\Type\Composer\RepositoryType'
        );
    }

    /**
     * @test
     */
    public function shouldReturnName()
    {
        $type = new RepositoryType();

        $this->assertEquals('oro_composer_repository', $type->getName());
    }

    /**
     * @test
     */
    public function shouldAddDataClassDuringSettingDefaults()
    {
        $type = new RepositoryType();

        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => 'Oro\Bundle\DistributionBundle\Entity\Composer\Repository']);

        $type->setDefaultOptions($resolver);
    }

    /**
     * @test
     */
    public function shouldBuildForm()
    {
        $type = new RepositoryType();

        $builder = $this->createConstructorLessMock('Symfony\Component\Form\FormBuilder');
        $builder->expects($this->at(0))
            ->method('add')
            ->with('type', 'choice', ['choices' => ['composer' => 'composer', 'vcs' => 'vcs', 'pear' => 'pear']])
            ->will($this->returnValue($builder));

        $builder->expects($this->at(1))
            ->method('add')
            ->with('url', 'text', ['required' => true]);

        $type->buildForm($builder, []);
    }
}
