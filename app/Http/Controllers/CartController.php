<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Notification as FacadesNotification;
use App\Notifications\Order;

class CartController extends Controller
{
    public function add(Product $product)
    {
        $user = auth()->user();
        if($user->products()->where('product_id',$product->id)->exists())
        {
            $quantity = $user->products()->find($product->id)->pivot->quantity + 1;
            $user->products()->updateExistingPivot($product->id,['quantity' => $quantity, 'total_price' => $product->price * $quantity]);
        }
        else
        {
            $user->products()->attach($product,['quantity' => 1, 'total_price' => $product->price]);
        }

        $count = 0;
        foreach($user->products as $product)
            $count+= $product->pivot->quantity;

        return response()->json([
            'message' => 'Product added successfully',
            'number_of_products_in_the_cart' => $count
        ]);
    }

    public function remove(Product $product)
    {
        $user = auth()->user();
        if(!$user->products()->where('product_id',$product->id)->exists())
            return response()->json(['error' => 'Product not found'], 404);

        if($user->products()->find($product->id)->pivot->quantity == 1)
        {
            $user->products()->detach($product);
            return response()->json('Product removed successfully');
        }
        else
        {
            $quantity = $user->products()->find($product->id)->pivot->quantity - 1;
            $user->products()->updateExistingPivot($product->id,['quantity' => $quantity, 'total_price' => $product->price * $quantity]);
            return response()->json([
                'message' => 'Quantity of products has decreased',
                'quantity' => $quantity
            ]);
        }
    }

    public function index()
    {        
        return response()->json($this->get_content_of_cart(auth()->user()));
    }
    
    
    public function order(Product $product)
    {

        $data = request()->validate([
            'location' => 'required | string | min:3 | max:100',
        ]);
        
        $user = auth()->user();
        FacadesNotification::send(User::where('role','admin')->get(),new Order($user, $data['location'], $this->get_content_of_cart($user)));

    }

    protected function get_content_of_cart(User $user)
    {
        $data = [];
        $bill = 0;
        foreach($user->products as $product)
            {
                $data[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'description' => $product->description,
                'available_quantity' => $product->available_quantity,
                'market_id' => $product->market->id,
                'price_for_one_piece' => $product->price,
                'quantity' => $product->pivot->quantity,
                'total_price' => $product->pivot->total_price
                ];
                $bill+= $product->pivot->total_price;
            }   
        return [
            'data' => $data,
            'bill' => $bill
        ];
    }
}
