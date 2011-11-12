<?php

namespace AY\GeneralBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Collection;

class SubscribeType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options) {
        $builder->add('email', 'email');
        $builder->add('phone', 'text');
    }

    public function getName() {
        return 'subscribe_email';
    }

    public function getDefaultOptions(array $options) {
        $collectionConstraint = new Collection(array(
            'email' => new Email(array('message' => 'Invalid email address')),
            'phone' => new Regex('/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/'),
        ));

        $options['validation_constraint'] = $collectionConstraint;

        return $options;
    }
}
