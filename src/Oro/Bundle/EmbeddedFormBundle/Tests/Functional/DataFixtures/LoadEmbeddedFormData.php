<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Tests\Functional\Stubs\EmbeddedFormStub;

class LoadEmbeddedFormData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const EMBEDDED_FORM = 'embedded_form';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $embeddedForm = new EmbeddedForm();
        $embeddedForm->setFormType(EmbeddedFormStub::class);
        $embeddedForm->setCss('input { color: red; }');
        $embeddedForm->setSuccessMessage('Form has been submitted successfully');
        $embeddedForm->setTitle('Send Feedback');
        $embeddedForm->setOwner($manager->getRepository('OroOrganizationBundle:Organization')->getFirst());

        $this->addReference(self::EMBEDDED_FORM, $embeddedForm);

        $manager->persist($embeddedForm);
        $manager->flush();
    }
}
