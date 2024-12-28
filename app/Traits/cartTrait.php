<?php   

namespace App\Traits;

use App\Models\User;

trait cartTrait {
    use storeImagesTrait;

    public function get_content_of_cart(User $user)
        {
            $bill = 0;
            $markets = [];
            foreach($user->cartItems as $product)
                {
                    $cart[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'image' => $this->get_image($product),
                    'description' => $product->description,
                    'market_id' => $product->market->id,
                    'price_for_one_piece' => $product->price,
                    'quantity' => $product->pivot->quantity,
                    'total_price' => $product->pivot->total_price
                    ];

                    // Calculate the total price
                    $bill+= $product->pivot->total_price;

                    // Calculate the total number of markets
                    if(!in_array($product->market->id, $markets))
                        $markets[] = $product->market->id;
                }   
            return [
                'products' => $cart?? null, 
                'bill' => $bill,
                'delivery_cost' => count($markets) * 5000,
                'total_bill' => $bill + count($markets) * 5000,
                'delivery_time' => count($markets) * 15
            ];
        }
}