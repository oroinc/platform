<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;

class EmailFolderTreeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            if ($data !== null) {
                return;
            }

            $event->setData(new ArrayCollection());
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $collection = $event->getForm()->getData();
            $data = $event->getData();
            if (!$data || !$collection instanceof Collection) {
                return;
            }

            array_map(
                [$collection, 'add'],
                array_map([$this, 'createFolder'], $data)
            );
        });
    }

    /**
     * @param array $data
     *
     * @return EmailFolder
     */
    protected function createFolder(array $data)
    {
        return (new EmailFolder())
            ->setSyncEnabled(isset($data['syncEnabled']))
            ->setFullName($data['fullName'])
            ->setName($data['name'])
            ->setType($data['type'])
            ->setSubFolders(new ArrayCollection(array_map(
                [$this, 'createFolder'],
                isset($data['subFolders']) ? $data['subFolders'] : []
            )));
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_email_email_folder_tree';
    }
}
