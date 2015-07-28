<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitAclMultiSelectType;

class BusinessUnitAclMultiSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var BusinessUnitAclMultiSelectType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType = new BusinessUnitAclMultiSelectType($this->entityManager);
    }

    public function testBuildForm()
    {
        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->once())
            ->method('addModelTransformer');
        $this->formType->buildForm($builder, ['entity_class' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit']);
    }

    public function testSetDefaultOptions()
    {
        $optionResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $optionResolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'autocomplete_alias' => 'user_business_units',
                'configs' => [
                    'permission' => 'VIEW',
                    'entity_name' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                    'multiple' => true,
                    'width' => '400px',
                    'placeholder' => 'oro.business_unit.form.choose_business_user',
                    'allowClear' => true,
                ]
            ]);
        $this->formType->setDefaultOptions($optionResolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_jqueryselect2_hidden', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_business_unit_multiselect', $this->formType->getName());
    }
}
