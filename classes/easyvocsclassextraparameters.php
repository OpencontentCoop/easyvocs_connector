<?php

class EasyVocsClassExtraParameters extends OCClassExtraParametersHandlerBase
{

    const IDENTIFIER = 'easyvocs';

    private $data;

    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    public function getName()
    {
        return 'Attributi mappabili a uso di EasyVocs';
    }

    public function attributes()
    {
        $attributes = parent::attributes();

        $attributes[] = 'enable_mapper';
        return $attributes;
    }

    public function attribute($key)
    {
        switch ($key) {
            case 'enable_mapper':
                return $this->getAttributeIdentifierListByParameter('enable_mapper', 1, false);
        }

        return parent::attribute($key);
    }
}
