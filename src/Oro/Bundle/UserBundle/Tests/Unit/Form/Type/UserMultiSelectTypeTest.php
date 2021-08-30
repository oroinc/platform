<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserMultiSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var UserMultiSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);

        $this->type = new UserMultiSelectType($this->em);
    }

    public function testBuildView()
    {
        $builder = $this->createMock(FormBuilder::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->isInstanceOf(EntitiesToIdsTransformer::class));

        $this->type->buildForm($builder, ['entity_class' => User::class]);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroJquerySelect2HiddenType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_user_multiselect', $this->type->getName());
    }
}
