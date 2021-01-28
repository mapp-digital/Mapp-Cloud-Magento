<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\Framework\DataObject;

class AbstractData extends DataObject
{

    /**
     * @var array
     */
    protected $_data = [];

    public function __construct()
    {
    }

    /**
     * @param mixed $data
     *
     * @return array|string
     */
    private function decode($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'decode'], $data);
        }

        if (is_object($data)) {
            $tmp = clone $data;
            foreach ($data as $k => $var) {
                $tmp->{$k} = $this->decode($var);
            }
            return $tmp;
        }

        if (is_string($data)) {
            return html_entity_decode($data);
        }

        return $data;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        if (!empty($name)) {
            $this->_data[$name] = $this->decode($value);
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setArray($name, $value)
    {
        if (!empty($name)) {
            if(!isset($this->_data[$name])) {
                $this->_data[$name] = array();
            }
            $this->_data[$name][] = $value;
        }
    }


    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        return '';
    }
}
