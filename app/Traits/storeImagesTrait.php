<?php

namespace App\Traits;

trait storeImagesTrait {
public  function storeImage($image, $folder)
{
    $extension = $image->getClientOriginalExtension();
    $name = time() . '.' . $extension;
    $image->move($folder, $name);
    return $name;
}

public function updateImage($newImage,$folder,$lastImage)
{
    $extension = $newImage->getClientOriginalExtension();
    $name = time() . '.' . $extension;
    $newImage->move($folder, $name);
    if(file_exists(public_path($folder . '/' . $lastImage)))
        unlink(public_path($folder . '/' . $lastImage));
    return $name;
}
}
