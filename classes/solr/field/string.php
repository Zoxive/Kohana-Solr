<?php defined('SYSPATH') or die('No direct script access.');

class Solr_Field_String extends Solr_Field
{
    const TYPE = 'string';

    public function __construct($name)
    {
        parent::__construct($name, self::TYPE);
    }

    static public function generateXML(DOMElement $fieldtype)
    {
        $fieldtype->setAttribute('name', self::TYPE);
        $fieldtype->setAttribute('class', 'solr.StrField');
        $fieldtype->setAttribute('sortMissingLast', 'true');
        $fieldtype->setAttribute('omitNorms', 'true');

        return $fieldtype;
    }
}
