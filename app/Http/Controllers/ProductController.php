<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Product;
use App\Traits\storeImagesTrait;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use storeImagesTrait;

    public function store(Market $market)
    {
        // Validate incoming request data
        $data = $this->validate_product();

        $data['market_id'] = $market->id;

        // Store image in 'Public' folder
        if(isset($data['image']))
            $data['image'] = $this->storeImage($data['image'],'images/products');

        // Create product
        $product = Product::create($data);

        return response()->json([
            'رسالة' => 'تم إنشاء المنتج بنجاح',
            'message' => 'Product created successfully',
            'product' => $product,
            'image_of_product' => $this->get_image($product),
        ]);
    }

    
    
    public function show(Product $product)        // Get the product with its image
    {
        // Check if product is existed
        if(!isset($product))
            return response()->json([
                'خطأ' => 'المنتج غير موجود',
                'error' => 'Product not found',
            ], 404);

        return response()->json([
            'product' => $product ,
            'image_of_product' => $this->get_image($product) ,
        ]);
    }

    
    
    public function update(Product $product)
    {
        // Validate incoming request data
        $data = $this->validate_product();

        // Update the image of the product in 'Public' file
        if(isset($data['image']))
            $data['image'] = $this->updateImage($data['image'],'images/products',$product->image);

        // Update the data
        $product->update($data);

        return response()->json([
            'رسالة' => 'تم تعديل معلومات المنتج بنجاح',
            'message' => 'Data updated successfully',
            'product' => $product,
            'image_of_product' => $this->get_image($product),
        ]);    
    }

    
    
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json([
            'رسالة' => 'تم حذف المنتج بنجاح',
            'message' => 'Product deleted successfully',
        ]);
    }



    public function add_to_favorites(Product $product)
    {
        // Check if product is existed
        if(!isset($product))
            return response()->json([
                'خطأ' => 'المنتج غير موجود',
                'error' => 'Product not found',
            ], 404);
            
        // Add to favorites products
        auth()->user()->favoritesProducts()->attach($product);

        return response()->json([
            'رسالة' => 'تم إضافة المنتج إلى المفضلة',
            'message' => 'Product has been added to favorites',
        ]);
    }



    public function remove_from_favorites(Product $product)
    {
        // Check if product is existed
        if(!isset($product))
            return response()->json([
                'خطأ' => 'المنتج غير موجود',
                'error' => 'Product not found',
            ], 404);
            
        // Remove from favorites products
        auth()->user()->favoritesProducts()->detach($product);

        return response()->json([
            'رسالة' => 'تم إضافة المنتج إلى المفضلة',
            'message' => 'Product has been added to favorites',
        ]);
    }


   
    public function index_favorites()   // Get all favorites products
    {
        $favorites = auth()->user()->favoritesProducts;

        return response()->json([
            'favorite_products' => $favorites
        ]);
    }



    public function search(Request $request)    // Search about products
    {
        $products = Product::where('name',$request->name)->get();

        return response()->json([
            'products' => $products
        ]);
    }



    protected function validate_product()       // Validate incoming request data
    {
        return request()->validate([
            'name' => 'required | min:3 | max:20 | string | unique:App\Models\Product,name ',
            'image' => 'nullable | image | max:5120',
            'description' => 'required | min:3 | max:200 | string',
            'price' =>  'required | numeric | min: 0',
            'available_quantity' => 'required | numeric | min: 1'
        ]);
    }
}
