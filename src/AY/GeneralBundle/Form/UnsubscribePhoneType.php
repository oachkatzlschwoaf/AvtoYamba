<?php

namespace AY\GeneralBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Collection;

class UnsubscribePhoneType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options) {
        $builder->add('phone', 'text');
        $builder->add('code',  'text');
    }

    public function getName() {
        return 'unsubscribe_phone';
    }

    public function getDefaultOptions(array $options) {
        $collectionConstraint = new Collection(array(
            'phone' => new Regex(
                array(
                    'pattern' => '/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/',
                    'message' => 'опа!'
                )
            )
        ));

        $options['validation_constraint'] = $collectionConstraint;

        return $options;
    }
}
