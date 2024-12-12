<?php

namespace App\Traits;

trait storeImagesTrait {
public  function storeImage($image, $folder)
{
    // Get extension of image
    $extension = $image->getClientOriginalExtension();

    // Create the name of image by merging time with extension
    $name = time() . '.' . $extension;

    // Store image
    $image->move($folder, $name);

    return $name;
}

public function updateImage($newImage, $folder, $lastImage)
{
    // Get extension of image
    $extension = $newImage->getClientOriginalExtension();

    // Create the name of image by merging time with extension
    $name = time() . '.' . $extension;

    // Store image
    $newImage->move($folder, $name);

    // Delete old image
    if(file_exists(public_path($folder . '/' . $lastImage)))
        unlink(public_path($folder . '/' . $lastImage));
    
    return $name;
}
}
