<?php defined('SYSPATH') or die('No direct script access.');

class Solr_Field_Core
{
    protected $_attributes = array
    (
        /**
         * Name of the field
         */
        'name'          =>  NULL,

        /**
         * Name of the defined field type, defined in schema.xml <types> section
         */
        'type'          =>  'text',

        /**
         * If this field should be retrievable from solr
         */
        'stored'        =>  FALSE,

        /**
         * Value used when none is set
         */
        'default'       =>  NULL,

        /**
         * Makes this field required. Used for Primary Keys
         */
        'required'      =>  NULL,

        /**
         * If this field can contain multiple values per document (ie: category hiearchy, tags)
         */
        'multiValued'   =>  FALSE,

        /**
         * If this field should be stored using gzip compression
         */
        'compressed'    =>  NULL,

        /**
         * Set to true to omit the norms associated with this field
		 * this disables length normalization and index-time boostinf ro the field, and saves some memory)
		 * Only full-text fields or fields that need an index-time boost need norms
         */
        'omitNorms'     =>  NULL,

        /**
         * Set to true to store the term vector for a given field.
		 * When using MoreLikeThis, fields used for similarity should be stored for best performance.
         */
        'termVectors'   =>  NULL,

		/**
         * Store position information with the term vector.
		 * This will increase storage costs.
         */
        'termPositions'	=>	NULL,
	
		/**
         * Store offset information with the term vector.
		 * This will increase storage costs.
         */
		'termOffsets'	=>	NULL,
    );

    /**
     * XML Element Name
     *
     * Changable for dynamicField
     */
    protected $tag_name = 'field';

    static public function factory($name, $type)
    {
        $model = 'Solr_Field_'.$type;
        return new $model($name);
    }

    /**
     * Initalize the Solr_Field
     *
     * @return void
     */
    public function __construct($name, $type = NULL)
    {
        $this->_attributes['name'] = $name;

        if ($type !== NULL)
        {
            $this->type($type);
        }
    }

    public function tag_name()
    {
        return $this->tag_name;
    }

    public function as_array()
    {
        return $this->_attributes;
    }

    public function __get($attribute)
    {
        if (isset($this->_attributes[$attribute]))
        {
            return $this->_attributes[$attribute];
        }
        throw new Solr_Exception(':name field does not have an attribute :attribute',
            array('name' => get_class($this), ':attribute' => $attribute));
    }

    /**
     * Sets the current field to be indexed, unless passed false
     *
     * @param   boolean     $index
     * @return  this
     */
    public function index($index = TRUE)
    {
        $this->_attributes['index'] = (bool)$index;
        return $this;
    }

    public function stored($stored = TRUE)
    {
        $this->_attributes['stored'] = (bool)$stored;
        return $this;
    }

    public function multi_value($multi_value = TRUE)
    {
        $this->_attributes['multiValued'] = (bool)$multi_value;
        return $this;
    }

    public function omit_norms($omit_norms = TRUE)
    {
        $this->_attributes['omitNorms'] = (bool)$omit_norms;
        return $this;
    }

    public function required($required = TRUE)
    {
        $this->_attributes['required'] = (bool)$required;
        return $this;
    }

    public function set_default($def = NULL)
    {
        $this->_attributes['default'] = $def;
        return $this;
    }

    public function compress($compress = TRUE)
    {
        $this->_attributes['compressed'] = (bool)$compress; 
        return $this;
    }

    public function term_vectors($term_vectors = TRUE)
    {
        $this->_attributes['termVectors'] = (bool)$term_vectors;
        return $this;
    }

    public function term_positions($term_positions = TRUE)
    {
        $this->_attributes['termPositions'] = (bool)$term_positions;
        return $this;
    }

    public function term_offsets($term_offsets = TRUE)
    {
        $this->_attributes['termOffsets'] = (bool)$term_offsets;
        return $this;
    }

    /**
     * Sets the type of Solr Field
     * 
     * @param   string  $type
     * @return  string
     */
    protected function type($type)
    {
        return $this->_attributes['type'] = $type;
    }

    public function generateFieldXML(DOMElement $ele)
    {
        foreach ($this->_attributes as $name => $value)
        {
            if (!is_null($value))
            {
                if (is_bool($value))
                {
                    $ele->setAttribute($name, $value? 'TRUE' : 'FALSE');
                }
                else
                {
                    $ele->setAttribute($name, $value);
                }
            }
        }

        return $ele;
    }

    /**
     * Generates the XML for the field to be added to ehe schema.xml
     *
     * @param DOMElement @fieldtype     A blank DOMElement object
     * @return DOMElement
     */
    static public function generateXML(DOMElement $ele)
    {
        throw new Solr_Exception('generateXML must be implemented for field classes');
    }
}
