<?php

namespace App\Traits;

use App\Models\Market;
use App\Models\Product;

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



public function get_image($object)
    {
        if($object instanceof Product)
            $folder = 'products';
        else if($object instanceof Market)
            $folder = 'markets';

        // Get path of image
        $imagePath = public_path("images/$folder/" . $object->image);

        // Get data of image
        if (is_file($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            return $imageData;
        }
        else
            return null;
    }
}
