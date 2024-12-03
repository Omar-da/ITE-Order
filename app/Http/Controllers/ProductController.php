<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Product;
use App\Traits\storeImagesTrait;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use storeImagesTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function store(Market $market)
    {
        $data = $this->validate_product();

        $data['market_id'] = $market->id;

        if(isset($data['image']))
            $data['image'] = $this->storeImage($data['image'],'images/products');

        $product = Product::create($data);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
            'image_of_product' => $this->get_image($product),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            'product' => $product ,
            'image_of_product' => $this->get_image($product) ,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Product $product)
    {
        $data = $this->validate_product();

        if(isset($data['image']))
            $data['image'] = $this->updateImage($data['image'],'images/products',$product->image);

        $product->update($data);

        return response()->json([
            'message' => 'Data updated successfully',
            'product' => $product,
            'image_of_product' => $this->get_image($product),
        ]);    
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    protected function get_image(Product $product)
    {
        $imagePath = public_path('images/products/' . $product->image);
        if (is_file($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            return $imageData;
        }
        else
            return null;
    }

    protected function validate_product()
    {
        return request()->validate([
            'name' => 'required | min:3 | max:20 | string | unique:App\Models\Product,name ',
            'image' => 'nullable | image | max:5120',
            'description' => 'required | min:3 | max:200 | string',
            'price' =>  'required | numeric',
            'available_quantity' => 'required | numeric | min: 1'
        ]);
    }
}
