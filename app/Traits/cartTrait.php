<?php   

namespace App\Traits;

use App\Models\User;

trait cartTrait {
    use storeImagesTrait;

    public function get_content_of_cart(User $user)
        {
            $bill = 0;
            foreach($user->products as $product)
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
                }   
            return [
                'products' => $cart?? null, 
                'bill' => $bill
            ];
        }
}