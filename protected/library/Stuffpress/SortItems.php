<?php
class Stuffpress_SortItems
{
    protected $object_array;
    
    protected $m = 1;
   
    private function _comp($a,$b)
    {
        $v1 = $a->getTimestamp();
        $v2 = $b->getTimestamp();
        
        if ($v1 == $v2) {
        	$result = 0;
        }
        else {
        	$result = ($v1 < $v2) ? -1 : 1; 
        }
        
        return $result * $this->m; 
    }
   
    public function sort(&$object_array, $desc=0)
    {
        $this->object_array = $object_array;
        if($desc) $this->m = -1; 
        usort($object_array, array($this, "_comp"));
    }
}