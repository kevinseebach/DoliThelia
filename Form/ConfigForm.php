<?php

namespace Dolithelia\Form;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\ConfigQuery;
use Dolithelia\Dolithelia;


class ConfigForm extends BaseForm
{

    protected function buildForm()
    {
        $translator = Translator::getInstance();
        $this->formBuilder
        ->add(
            'base_url',
            'text',
            [
                'constraints' => [
                    new NotBlank()
                ],
                'label' => "URL de votre instance Dolibarr",
                'data' => ConfigQuery::read('Dolithelia_base_url')
            ]
        )
        ->add(
            'api_key',
            'text',
            [
                'constraints' => [
                    new NotBlank()
                ],
                'label' => "Votre clef API Dolibarr",
                'data' => ConfigQuery::read('Dolithelia_api_key')
            ]
        )
        ;
    }
    /**
     * @return string the name of you form. This name must be unique
     */
    public function getName()
    {
        return 'Dolithelia_config';
    }
}
