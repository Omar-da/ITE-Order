<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Traits\cartTrait;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use cartTrait;

    public function add(Product $product)
    {
        $user = auth()->user();

        // Check if the product is existed 
        if(!$product)
            return response()->json(['error' => 'Product not found'], 404);

        // Check if there is enough quantity
        if(!$product->available_quantity)
            return response()->json('Sorry, product out of stock');

        // Decrease the quantity of the product from the market
        $product->update([
            'available_quantity' => $product->available_quantity - 1
        ]);

        // Increase the quantity in the cart if the product is existed 
        if($user->products()->where('product_id',$product->id)->exists())
        {
            $quantity = $user->products()->find($product->id)->pivot->quantity + 1;
            $user->products()->updateExistingPivot($product->id,['quantity' => $quantity, 'total_price' => $product->price * $quantity]);
        }
        // Add the product to the cart for the first time
        else
        {
            $user->products()->attach($product,['quantity' => 1, 'total_price' => $product->price]);
        }

        // Calculate the number of products in the cart in pieces
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

        // Check if the product is existed in the cart
        if(!$user->products()->where('product_id',$product->id)->exists())
            return response()->json(['error' => 'Product not found'], 404);

        // Remove the product if there is just one piece of it
        if($user->products()->find($product->id)->pivot->quantity == 1)
        {
            $user->products()->detach($product);
            $quantity = 0;
        }
        // Decrease the quantity of product if there are more than one piece
        else
        {
            $quantity = $user->products()->find($product->id)->pivot->quantity - 1;
            $user->products()->updateExistingPivot($product->id,['quantity' => $quantity, 'total_price' => $product->price * $quantity]);
        }
        
        // Increase the quantity of the products in the market
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
    

    
    public function order(Request $request)
    {
        // Validate location input
        $data = $request->validate([
            'location' => 'required | string | min:3 | max:100',
        ]);
        
        // Data of order
        $user = auth()->user();
        $cart = $this->get_content_of_cart($user);
        $location = $data['location'];

        // Create order
        $order = Order::create([
            'user_id' => $user->id,
            'cart' => json_encode($cart),
            'location' => $location,
            'status' => 'Waiting for response'
        ]);
        
        // Create notification
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

        // Modifying the quantity of the products in markets 
        foreach($user->products as $product_in_cart)
        {
            $product_in_market = Product::find($product_in_cart->id);
            $product_in_market->update([
                'available_quantity' => $product_in_market->available_quantity + $product_in_cart->quantity
            ]);
        }

        // Destroy the cart
        $user->products()->detach();

        return response()->json('The cart is empty');
    }
}
