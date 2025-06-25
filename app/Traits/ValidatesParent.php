<?php

namespace App\Traits;

use App\Models\AreaStructure;

trait ValidatesParent
{
   protected function isValidParent($item, $newParentId, $parentRelation = 'parent')
{
    // নিজেকে parent হিসেবে সেট করা যাবে না
    if (!$newParentId || $item->id == $newParentId) {
        return false;
    }

    // নতুন parent খুঁজে আনা — একই model class
    $parent = get_class($item)::find($newParentId);

    while ($parent) {
        if ($parent->id == $item->id) {
            return false; // সাইক্লিক রিলেশন
        }

        // যদি parent relation method না থাকে, break
        if (!method_exists($parent, $parentRelation)) {
            break;
        }

        // parent relation call (dynamic)
        $parent = $parent->$parentRelation;
    }

    return true;
}

}
