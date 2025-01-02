<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Traits\storeImagesTrait;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    use storeImagesTrait;

    public function index()
    {
        // Get markets
        $markets = Market::all();

        // Combine markets with their images
        $marketsWithImages = [];
        foreach($markets as $market)
            $marketsWithImages[] = [
                'market' => $market,
                'image' => $this->get_image($market),
            ];

        // Return markets with their images
        return response()->json([
            'markets' => $marketsWithImages,
        ]);
    }

    
    
    public function store()
    {
        // Validate incoming request data
        $data = $this->validate_market();

        // Store image in 'Public' folder
        if(isset($data['image']))
            $data['image'] = $this->storeImage($data['image'],'images/markets');

        // Create market
        $market = Market::create($data);

        return response()->json([
            'رسالة' => 'تم إنشاء المطعم بنجاح',
            'message' => 'Market created successfully',
            'market' => $market,
            'image_of_market' => $this->get_image($market),
        ]);
    }

    
    
    public function show($market)        // Get market with its image and products
    {
        $market = Market::find($market);
        
        // Check if market is existed
        if(!isset($market))
            return response()->json([
                'خطأ' => 'المطعم غير موجود',
                'error' => 'Market not found',
            ], 404);

        // Combine products with their images
        $productsWithImages = [];
        foreach($market->products as $product)
            $productsWithImages[] = [
                'product' => $product,
                'image' => $this->get_image($product),
            ];

        return response()->json([
            'market' => $market ,
            'image' => $this->get_image($market),
            'products' => $productsWithImages
        ]);
    }

    
    
    public function update(Market $market)
    {
        // Validate incoming request data
        $data = $this->validate_market();

        // Update the image of the market in 'Public' file
        if(isset($data['image']))
            $data['image'] = $this->updateImage($data['image'],'images/markets',$market->image);

        // Update the data
        $market->update($data);

        return response()->json([
            'رسالة' => 'تم تعديل معلومات المطعم بنجاح',
            'message' => 'Data updated successfully',
            'market' => $market,
            'image_of_market' => $this->get_image($market),
            'products' => $market->products
        ]);
    }

    
    
    public function destroy(Market $market)     // Delete market
    {
        $market->delete();

        return response()->json([
            'رسالة' => 'تم حذف المطعم بنجاح',
            'message' => 'Market deleted successfully',
        ]);
    }



    public function search(Request $request)    // Search about markets
    {
        $markets = Market::where('name','like', "%{$request->name}%")->get();

        return response()->json([
            'markets' => $markets
        ]);
    }



    protected function validate_market()        // Validate incoming request data
    {
        return request()->validate([
            'name' => 'required | min:3 | max:20 | string | unique:App\Models\Market,name ',
            'location' => 'required | min:3 | max:100 | string',
            'description' => 'required | min:3 | max:200 | string',
            'image' =>  'nullable | image | max:5120',
        ]);
    }
}
