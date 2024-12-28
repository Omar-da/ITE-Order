<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Services\FcmService;
use App\Traits\cartTrait;

class OrderController extends Controller
{
    use cartTrait;
    
    public function __construct(public FcmService $fcmService)    // Injection the fcm sevice
    {}



    public function index()    // Get all orders
    {
        return response()->json([
            'orders' => Order::where('user_id', auth()->user()->id)->withTrashed()->get()
        ]);
    }



    public function index_delivered_orders()    // Get only delivered orders
    {
        return response()->json([
            'delivered_orders' => Order::where('user_id', auth()->user()->id)->where('status','delivered')->get()
        ]);
    }



    public function index_in_way_orders()   // Get only not delivered orders
    {
        return response()->json([
            'delivered_orders' => Order::where('user_id', auth()->user()->id)->whereIn('status',['waiting_for_response','period_of_editing','in_way'])->get()
        ]);
    }
    
    
    
    public function update(Order $order)
    {
        $user = auth()->user();
        
        // Prevent updating if order has delivered or in way
        if($order->status == 'In way' || $order->status == 'Delivered')
        {
            // Notification
            foreach($user->fcm_tokens as $fcm_token)
                $this->fcmService->sendNotification($fcm_token,'Order',__('order.editing_order_is_unavailable'));  

            return response()->json([
                'message' => 'Sorry, order can not be changed',
            ]);
        }


        // Validate location input
        $data = request()->validate([
            'location' => 'required | string | min:3 | max:100'
        ]);

        // Get the cart
        $cart = $this->get_content_of_cart($user);

        // Prevent delete the order
        if($cart['products'] == null)
        {
            // Notification
            foreach($user->fcm_tokens as $fcm_token)
                $this->fcmService->sendNotification($fcm_token,'Order',__('order.one_product_at_least'));

            return response()->json([
                'message' => 'Sorry, you must add one product at least',
            ]);
        }

        $location = $data['location'];

        // Update the order
        $order->update([
            'cart' => $cart,
            'location' => $location
        ]);
        
        // Update the notification
        $noti = Notification::where('order_id', $order->id)->first();
        $noti->update([
            'cart' => $cart,
            'location' => $location,
            'updated' => true
        ]);
        
        // Notification
        foreach($user->fcm_tokens as $fcm_token)
            $this->fcmService->sendNotification($fcm_token,'Order',__('order.update'));  

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order
        ]);
    }



    public function destroy(Order $order)
    {
        $user = auth()->user();

        // Prevent deleting the order if it is in 'Waiting for response' status
        if($order->status != 'Waiting for response')
        {
            // Notification
            foreach($user->fcm_tokens as $fcm_token)
                $this->fcmService->sendNotification($fcm_token,'Order',__('order.order.deleting_order_is_unavailable'));

            return response()->json([
                'message' => 'Sorry, order can not be deleted',
            ]);
        }
    
        // Delete the order (soft delete)
        $order->update([
            'status' => 'Deleted'
        ]);
        $order->delete();
    
        // Modify the quantity of the products in markets 
        foreach($user->cartItems as $item)
        {
            $product_in_market = Product::find($item->id);
            $product_in_market->update([
                'available_quantity' => $product_in_market->available_quantity + $item->quantity
            ]);
        }

        // Destroy the cart
        $user->cartItems()->detach();

        // Delete the notification
        $noti = Notification::where('order_id', $order->id)->first();
        $noti->delete();
        
        // Notification
        foreach($user->fcm_tokens as $fcm_token)
            $this->fcmService->sendNotification($fcm_token,'Order',__('order.delete'));

        return response()->json([
            'message' => 'Order deleted successfully',
            'order' => $order
        ]);
    }



    public function admin_response(Order $order)        // Admin will accept or reject
    {
        $user = auth()->user();
        
        // Check if the order is waiting for response
        if($order->status != 'Waiting for response')
            return response()->json([
                'error' => 'Order not found',
            ], 404);

        // Check of the response of admin
        if(request()->approval == 'true')
            {
                // Modify the status of the order
                $order->update([
                    'status' => 'Period of editing'
                ]);
                
                // Notification
                foreach($user->fcm_tokens as $fcm_token)
                    $this->fcmService->sendNotification($fcm_token,'Order',__('order.accept'));

                return response()->json('Order has been approved');
            }
        else if(request()->approval == 'false')
            {
                // Delete the cart
                $user->cartItems()->detach();

                // Modify the status of the order
                $order->update([
                    'status' => 'Rejected'
                ]);

                // Delete order (soft delete)
                $order->delete();
         
                // Notification
                foreach($user->fcm_tokens as $fcm_token)
                    $this->fcmService->sendNotification($fcm_token,'Order',__('order.reject'));

                return response()->json([
                    'message' => 'Order has been rejected',
                ]);
            }
        else
            return response()->json('Invalid value');
    }



    public function period_of_editing_has_finished(Order $order)        // After 5 minutes from accepting the order
    {
        $user = auth()->user();

        // Modify the status of the order
        $order->update([
            'status' => 'In way'
        ]);

        // Delete the cart
        $user->cartItems()->detach();

        // Notification  
        foreach($user->fcm_tokens as $fcm_token)
            $this->fcmService->sendNotification($fcm_token,'Order',__('order.in_way'));

        return response()->json([
            'message' => 'Order in way',
        ]);
    }



    public function delivered(Order $order)        // After 30 minutes
    {
        // Modify the status of the order
        $order->update([
            'status' => 'Delivered'
        ]);

        // Notification
        foreach(auth()->user()->fcm_tokens as $fcm_token)
            $this->fcmService->sendNotification($fcm_token,'Order',__('order.deliver'));

        return response()->json([
            'message' => 'Order has delivered, thank you for your trust',
        ]);
    }



    public function restore($order)        // Resend the order
    {
        // Get the order by its id
        $order = Order::withTrashed()->find($order);

        // Get auth user
        $user = auth()->user();

        // Get the cart from the order
        $cart = json_decode($order->cart);

        foreach($cart['products'] as $cartItem)
        {
            // Check if the products are existed
            $product = Product::find($cartItem['product_id']);
            if(!$product)
            {
                // Notification
                foreach($user->fcm_tokens as $fcm_token)
                    $this->fcmService->sendNotification($fcm_token,'Order',__('order.resend_product_not_found'));

                return response()->json([
                    'error' => 'One of products has not been existed'
                ], 404);
            }

            // Check if there is enough quantity of products in markets
            $quantity = $cartItem['quantity'];
            if($product->available_quantity < $quantity)
                {
                    // Notification
                    foreach($user->fcm_tokens as $fcm_token)
                    $this->fcmService->sendNotification($fcm_token,'Order',__('order.resend_not_enough_quantity'));

                    return response()->json([
                        'error' => 'There is no enough quantity'
                    ], 404);
                }

            // Decrease the quantity of the product from the market
            $product->update([
                'available_quantity' => $product->available_quantity - $quantity
            ]);
        }
    
        // Resend the order
        $order->restore();

        // Modify the status of the order
        $order->update([
            'status' => 'Waiting for response',
            'is_restored' => true
        ]);

        // Create a new notification
        Notification::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'cart' => $order->cart,
            'location' => $order->location,
            'updated' => false
        ]);

        // Notification
        foreach($user->fcm_tokens as $fcm_token)
            $this->fcmService->sendNotification($fcm_token,'Order',__('order.resend'));

        return response()->json([
            'message' => 'The order has sent, you will receive response from admin with accepting or rejecting',
            'order' => $order
        ]);
    }  
    
    

    public function index_notifications()   // Get all notifications
    {
        return response()->json([
            'notifications' => Notification::all()
        ]);
    }
}
