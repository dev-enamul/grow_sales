<?php

namespace App\Traits;

use App\Models\AreaStructure;

trait ValidatesParent
{
    protected function isValidParent($structure, $newParentId)
    {
        if (!$newParentId || $structure->id == $newParentId) {
            return false;
        }

        $parent = AreaStructure::find($newParentId); 
        while ($parent) {
            if ($parent->id == $structure->id) {
                return false;  
            } 
            if (!method_exists($parent, 'parent')) {
                break;
            } 
            $parent = $parent->parent;
        } 
        return true;
    }
}
