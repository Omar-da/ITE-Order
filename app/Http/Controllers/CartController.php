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
            return response()->json([
            'خطأ' => 'المنتج غير موجود',
            'error' => 'Product not found',
            ], 404);

        // Check if there is enough quantity
        if(!$product->available_quantity)
            return response()->json([
            'رسالة' => 'عذراً نفدت الكمية',
            'message' => 'Sorry, product out of stock',
            ]);

        // Decrease the quantity of the product from the market
        $product->update([
            'available_quantity' => $product->available_quantity - 1
        ]);

        // Increase the quantity in the cart if the product is existed 
        if($user->cartItems()->where('product_id',$product->id)->exists())
        {
            $quantity = $user->cartItems()->find($product->id)->pivot->quantity + 1;
            $user->cartItems()->updateExistingPivot($product->id,['quantity' => $quantity, 'total_price' => $product->price * $quantity]);
        }
        // Add the product to the cart for the first time
        else
        {
            $user->cartItems()->attach($product,['quantity' => 1, 'total_price' => $product->price]);
        }

        // Calculate the number of products in the cart in pieces
        $count = 0;
        foreach($user->cartItems as $item)
            $count+= $item->pivot->quantity;

        return response()->json([
            'رسالة' => 'تم إضافة المنتج بنجاح',
            'message' => 'Product added successfully',
            'number_of_products_in_the_cart' => $count
        ]);
    }



    public function remove(Product $product)
    {
        $user = auth()->user();

        // Check if the product is existed in the cart
        if(!$user->cartItems()->where('product_id',$product->id)->exists())
            return response()->json([
            'خطأ' => 'المنتج غير موجود',
            'error' => 'Product not found',
            ], 404);

        // Remove the product if there is just one piece of it
        if($user->cartItems()->find($product->id)->pivot->quantity == 1)
        {
            $user->cartItems()->detach($product);
            $quantity = 0;
        }
        // Decrease the quantity of product if there are more than one piece
        else
        {
            $quantity = $user->cartItems()->find($product->id)->pivot->quantity - 1;
            $user->cartItems()->updateExistingPivot($product->id,['quantity' => $quantity, 'total_price' => $product->price * $quantity]);
        }
        
        // Increase the quantity of the products in the market
        $product->update([
            'available_quantity' => $product->available_quantity + 1
        ]);

        // Calculate the number of products in the cart in pieces
        $count = 0;
        foreach($user->cartItems as $item)
            $count+= $item->pivot->quantity;
        
        return response()->json([
            'رسالة' => 'تم إرجاع المنتج',
            'message' => 'Quantity of products has decreased',
            'number_of_products_in_the_cart' => $count
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
            'رسالة' => 'تم إرسال الطلب ، سيتم إرسال إجابة من المشرف بالقبول أو الرفض',
            'message' => 'The order has sent, you will receive response from admin with accepting or rejecting',
            'order' => $order
        ]);
    }



    public function destroy()
    {
        $user = auth()->user();

        // Modifying the quantity of the products in markets 
        foreach($user->cartItems as $item)
        {
            $product_in_market = Product::find($item->id);
            $product_in_market->update([
                'available_quantity' => $product_in_market->available_quantity + $item->quantity
            ]);
        }

        // Destroy the cart
        $user->cartItems()->detach();

        return response()->json([
            'رسالة' => 'السلة فارغة',
            'message' => 'The cart is empty',
        ]);
    }
}
