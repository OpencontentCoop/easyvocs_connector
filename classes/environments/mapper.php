<?php

use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\ClassRepository;

class MapperEnvironmentSettings extends DefaultEnvironmentSettings
{
    protected static $classDefinitions = array();

    protected static $mapperExtraParameters = array();

    public function filterContent(Content $content)
    {
        if (!isset(self::$classDefinitions[$content->metadata->classIdentifier])) {
            $classRepository = new ClassRepository();
            self::$classDefinitions[$content->metadata->classIdentifier] = $classRepository->load($content->metadata->classIdentifier);
        }

        $mapperContent = (array)self::$classDefinitions[$content->metadata->classIdentifier];
        $mapperContent['id'] = $this->requestBaseUri . 'read/' . $content->metadata->id;

        foreach ($mapperContent['fields'] as $index => $mappedField) {
            $mapperContent['fields'][$index]['isPartOfTheHash'] = $this->isPartOfTheHash($content->metadata->classIdentifier, $mappedField['identifier']);
            $mapperContent['fields'][$index]['value'] = $this->getFieldValue($content, $mappedField);
        }
        ksort($mapperContent);

        return $mapperContent;
    }

    private function isPartOfTheHash($classIdentifier, $fieldIdentifier)
    {
        if (!isset(self::$mapperExtraParameters[$classIdentifier])) {
            self::$mapperExtraParameters[$classIdentifier] = OCClassExtraParameters::fetchByHandlerAndClassIdentifier(MapperClassExtraParameters::IDENTIFIER, $classIdentifier);
        }
        foreach (self::$mapperExtraParameters[$classIdentifier] as $extraParameter) {
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
                                $contentUri[] = $this->requestBaseUri . 'read/' . $related['id'];
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