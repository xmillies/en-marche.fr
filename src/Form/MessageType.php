<?php

namespace AppBundle\Form;

use AppBundle\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class, [
                'filter_emojis' => true,
            ])
            ->add('subject', TextType::class, [
                'filter_emojis' => true,
            ])
            ->add('content', PurifiedTextareaType::class, [
                'attr' => [
                    'maxlength' => 5000,
                ],
                'filter_emojis' => true,
                'purifier_type' => 'enrich_content',
                'with_character_count' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Message::class);
    }
}
