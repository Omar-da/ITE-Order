<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Traits\cartTrait;

class CartController extends Controller
{
    use cartTrait;

    public function add(Product $product)
    {
        $user = auth()->user();
        if(!$product->available_quantity)
            return response()->json('Sorry, product out of stock');

        $product->update([
            'available_quantity' => $product->available_quantity - 1
        ]);

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
            $quantity = 0;
        }
        else
        {
            $quantity = $user->products()->find($product->id)->pivot->quantity - 1;
            $user->products()->updateExistingPivot($product->id,['quantity' => $quantity, 'total_price' => $product->price * $quantity]);
        }
        
        $product->update([
            'available_quantity' => $product->available_quantity + 1
        ]);

        return response()->json([
            'message' => 'Quantity of products has decreased',
            'quantity' => $quantity
        ]);
    }

    public function index()
    {        
        return response()->json(['cart' => $this->get_content_of_cart(auth()->user())]);
    }
    
    
    public function order()
    {
        $data = request()->validate([
            'location' => 'required | string | min:3 | max:100',
        ]);
        
        $user = auth()->user();
        $cart = $this->get_content_of_cart($user);
        $location = $data['location'];

        $order = Order::create([
            'user_id' => $user->id,
            'cart' => json_encode($cart),
            'location' => $location,
            'status' => 'Waiting for response'
        ]);
        

        Notification::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'cart' => json_encode($cart),
            'location' => $location,
            'updated' => false
        ]);
        
        return response()->json([
            'message' => 'The order has sent, you will receive response from admin with accepting or rejecting',
            'order' => $order
        ]);
    }

    public function destroy()
    {
        $user = auth()->user();

        foreach($user->products as $product_in_cart)
        {
            $product_in_market = Product::find($product_in_cart->id);
            $product_in_market->update([
                'available_quantity' => $product_in_market->available_quantity + $product_in_cart->quantity
            ]);
        }
        $user->products()->detach();

        return response()->json('The cart is empty');
    }
}
