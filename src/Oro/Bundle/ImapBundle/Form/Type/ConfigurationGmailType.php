<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ImapBundle\Form\Model\IMapGmailModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurationGmailType extends AbstractType
{
    const NAME = 'oro_imap_configuration_gmail';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('check', 'button', [
                'label'    => 'Connect',
//                'mapped'   => false
            ])
            ->add('token', 'hidden')
        ;

        $this->initEvents($builder);
    }

    /**
     * Update form if accountType is Other
     *
     * @param FormBuilderInterface $builder
     */
    protected function initEvents(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) {
            $IMapGmailModel = $formEvent->getData();
            $form = $formEvent->getForm();

            if ($IMapGmailModel instanceof IMapGmailModel) {
                if (!empty($IMapGmailModel->getToken())) {
                    $form->add('checkFolder', 'button', [
                        'label'    => 'Check Folder'
                    ])
                    ->add('folders', 'oro_email_email_folder_tree', [
                        'label'   => 'oro.email.folders.label', //$this->translator->trans('oro.email.folders.label'),
                        'attr'    => ['class' => 'folder-tree'],
                        'tooltip' => 'If a folder is uncheked, all the data saved in it will be deleted',
                    ]);

                    $form->remove('check');
                }
            }
        });
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\\Bundle\\ImapBundle\\Form\Model\\IMapGmailModel'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
