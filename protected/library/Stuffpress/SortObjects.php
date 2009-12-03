<?php
class Stuffpress_SortObjects
{
    protected $object_array;
    protected $sort_by;
   
    private function _comp($a,$b)
    {
        $key=$this->sort_by;
        $v1 = $a[$key];
        $v2 = $b[$key];
        
        if ($v1 == $v2) {
        	$result = 0;
        }
        else {
        	$result = ($v1 < $v2) ? -1 : 1; 
        }
        
        return $result; 
    }
   
    public function sort(&$object_array, $sort_by)
    {
        $this->object_array = $object_array;
        $this->sort_by      = $sort_by;
        usort($object_array, array($this, "_comp"));
    }
}