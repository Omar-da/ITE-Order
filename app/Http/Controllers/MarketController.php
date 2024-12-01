<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Traits\storeImagesTrait;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    use storeImagesTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $markets = Market::all();
        $imageArray = [];    

        $markets = $markets->map(function ($market) use (&$imageArray) {
            $imageArray[] = [
                'market_id' => $market->id,
                'image' => $this->get_image($market),
            ];
            return $market;
        });

        return response()->json([
            'markets' => $markets,
            'images_array' => $imageArray
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $data = $this->validate_market();

        if(isset($data['image']))
            $data['image'] = $this->storeImage($data['image'],'images/markets');

        $market = Market::create($data);

        return response()->json([
            'message' => 'Market created successfully',
            'market' => $market,
            'image_of_market' => $this->get_image($market),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Market $market)
    {
        return response()->json([
            'market' => $market ,
            'image_of_market' => $this->get_image($market) ,
            'products' => $market->products
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Market $market)
    {
        $data = $this->validate_market();

        if(isset($data['image']))
            $data['image'] = $this->updateImage($data['image'],'images/markets',$market->image);

        $market->update($data);

        return response()->json([
            'message' => 'Data updated successfully',
            'market' => $market,
            'image_of_market' => $this->get_image($market),
            'products' => $market->products
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Market $market)
    {
        $market->delete();
        return response()->json([
            'message' => 'Market deleted successfully'
        ]);
    }

    protected function get_image(Market $market)
    {
        $imagePath = public_path('images/markets/' . $market->image);
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            return $imageData;
        }
        else
            return null;
    }

    protected function validate_market()
    {
        return request()->validate([
            'name' => 'required | min:3 | max:20 | string | unique:App\Models\Market,name ',
            'location' => 'required | min:3 | max:100 | string',
            'description' => 'required | min:3 | max:200 | string',
            'image' =>  'nullable | image | max:5120',
        ]);
    }
}
