<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractEmbeddedFormDataFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getEmbeddedFormData() as $formData) {
            $embeddedForm = new EmbeddedForm();
            $embeddedForm->setFormType($formData['formType']);
            $embeddedForm->setCss('input { color: red; }');
            $embeddedForm->setSuccessMessage('Form has been submitted successfully');
            $embeddedForm->setTitle('Send Feedback');
            $embeddedForm->setOwner($manager->getRepository('OroOrganizationBundle:Organization')->getFirst());

            if (isset($formData['reference'])) {
                $this->addReference($formData['reference'], $embeddedForm);
            }

            $manager->persist($embeddedForm);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    abstract protected function getEmbeddedFormData(): array;
}
