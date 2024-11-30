<?php

namespace App\Traits;

trait storeImagesTrait {
public  function storeProfiles($image, $folder)
{
    $extension = $image->getClientOriginalExtension();
    $name = time() . '.' . $extension;
    $image->move($folder, $name);
    return $name;
}

}
