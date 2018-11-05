<?php

use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\ClassRepository;

class EasyVocsEnvironmentSettings extends DefaultEnvironmentSettings
{
    protected static $classDefinitions = array();

    protected static $easyvocsExtraParameters = array();

    public function filterContent(Content $content)
    {
        if (!isset(self::$classDefinitions[$content->metadata->classIdentifier])) {
            $classRepository = new ClassRepository();
            self::$classDefinitions[$content->metadata->classIdentifier] = $classRepository->load($content->metadata->classIdentifier);
        }

        $easyvocsContent = (array)self::$classDefinitions[$content->metadata->classIdentifier];
        $easyvocsContent['id'] = $this->request->getHostURI() . '/easyvocs/object/' . $content->metadata->id;

        foreach ($easyvocsContent['fields'] as $index => $mappedField) {
            $easyvocsContent['fields'][$index]['isPartOfTheHash'] = self::isPartOfTheHash($content->metadata->classIdentifier, $mappedField['identifier']);
            $easyvocsContent['fields'][$index]['value'] = $this->getFieldValue($content, $mappedField);
        }
        ksort($easyvocsContent);

        return $easyvocsContent;
    }

    public static function isPartOfTheHash($classIdentifier, $fieldIdentifier)
    {
        if (!isset(self::$easyvocsExtraParameters[$classIdentifier])) {
            self::$easyvocsExtraParameters[$classIdentifier] = OCClassExtraParameters::fetchByHandlerAndClassIdentifier(EasyVocsClassExtraParameters::IDENTIFIER, $classIdentifier);
        }
        foreach (self::$easyvocsExtraParameters[$classIdentifier] as $extraParameter) {
            if ($extraParameter->attribute('attribute_identifier') == $fieldIdentifier) {
                return true;
            }
        }
        return false;
    }

    private function getFieldValue(Content $content, $field)
    {
        $result = array();
        foreach ($content->data as $language => $data) {
            foreach ($data as $identifier => $value) {
                if ($identifier == $field['identifier']) {
                    $content = $value['content'];
                    if ($field['dataType'] == 'ezobjectrelationlist' || $field['dataType'] == 'ezobjectrelation') {
                        $contentUri = array();
                        foreach ($content as $related) {
                            $related = (array)$related;
                            if (isset($related['id'])) {
                                $contentUri[] = $this->request->getHostURI() . '/easyvocs/object/' . $related['id'];
                            }
                        }
                        $content = $contentUri;
                    } elseif ($field['dataType'] == 'ezimage' || $field['dataType'] == 'ezbinaryfile') {
                        if (isset($content['url'])) {
                            $content['url'] = $this->request->getHostURI() . $content['url'];
                        }
                    }
                    $result[$language] = $content;
                }
            }
        }

        return $result;
    }
}