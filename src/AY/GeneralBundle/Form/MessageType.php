<?php

namespace AY\GeneralBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options) {
        $builder
            ->add('number')
            ->add('user_name')
            ->add('text', 'textarea')
            ->add('image_upload', 'file')
        ;
    }

    public function getName() {
        return 'message';
    }

    public function getDefultOptions(array $options) {
        return array(
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'intention'       => 'message_id',
            'property_path'   => false,
        );
    }
}
