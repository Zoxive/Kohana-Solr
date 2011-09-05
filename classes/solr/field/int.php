<?php defined('SYSPATH') or die('No direct script access.');

class Solr_Field_Int extends Solr_Field
{
    const TYPE = 'int';

    public function __construct($name)
    {
        parent::__construct($name, self::TYPE);
    }

    static public function generateXML(DOMElement $fieldtype)
    {
        $fieldtype->setAttribute('name', self::TYPE);
        $fieldtype->setAttribute('class', 'solr.TrieIntField');
        $fieldtype->setAttribute('precisionStep', 0);
        $fieldtype->setAttribute('omitNorms', 'true');
        $fieldtype->setAttribute('positionIncrementGap', 0);

        return $fieldtype;
    }
}
