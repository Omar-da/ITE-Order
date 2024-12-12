<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Traits\cartTrait;

class OrderController extends Controller
{
    use cartTrait;
    
    public function index()
    {
        return response()->json([
            'orders' => Order::where('user_id', auth()->user()->id)->withTrashed()->get()
        ]);
    }



    public function update(Order $order)
    {
        // Prevent updating if order has delivered or in way
        if($order->status == 'In way' || $order->status == 'Delivered')
            return response()->json('Sorry, order can not be changed');

        $user = auth()->user();

        // Validate location input
        $data = request()->validate([
            'location' => 'required | string | min:3 | max:100'
        ]);

        // Get the cart
        $cart = $this->get_content_of_cart($user);

        // Prevent delete the order
        if($cart['products'] == null)
            return response()->json('Sorry, you must add one product at least');

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
        
        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order
        ]);
    }



    public function destroy(Order $order)
    {
        // Prevent deleting the order if it is in 'Waiting for response' status
        if($order->status != 'Waiting for response')
            return response()->json('Soryy, order can not be deleted');
    
        // Delete the order (soft delete)
        $order->update([
            'status' => 'Deleted'
        ]);
        $order->delete();
    
        // Delete the notification
        $noti = Notification::where('order_id', $order->id)->first();
        $noti->delete();
        
        return response()->json([
            'message' => 'Order deleted successfully',
            'order' => $order
        ]);
    }



    public function admin_response(Order $order)        // Admin will accept or reject
    {
        // Check if the order is waiting for response
        if($order->status != 'Waiting for response')
            return response()->json(['error' => 'Order not found'], 404);

        // Check of the response of admin
        if(request()->approval == 'true')
            {
                // Modify the status of the order
                $order->update([
                    'status' => 'Period of editing'
                ]);
                
                return response()->json('Admin has approved your order, and you have 5 minutes to make changes to it');
            }
        else if(request()->approval == 'false')
            {
                // Delete the cart
                auth()->user()->products()->detach();

                // Modify the status of the order
                $order->update([
                    'status' => 'Rejected'
                ]);

                // Delete order (soft delete)
                $order->delete();

                return response()->json('Admin has not approved your order, try again later');
            }
        else
            return response()->json('Invalid value');
    }



    public function period_of_editing_has_finished(Order $order)        // After 5 minutes from accepting the order
    {
        // Modify the status of the order
        $order->update([
            'status' => 'In way'
        ]);

        // Delete the cart
        $user = auth()->user();
        $user->products->detach();

        return response()->json('Order in way');
    }



    public function delivered(Order $order)        // After 30 minutes
    {
        // Modify the status of the order
        $order->update([
            'status' => 'Delivered'
        ]);

        return response()->json('Order has delivered, thank you for your trust');
    }



    public function restore($order)        // Resend the order
    {
        $order = Order::withTrashed()->find($order);
        $order->restore();

        // Modify the status of the order
        $order->update([
            'status' => 'Waiting for response'
        ]);

        // Create a new notification
        Notification::create([
            'order_id' => $order->id,
            'user_id' => auth()->user()->id,
            'cart' => $order->cart,
            'location' => $order->location,
            'updated' => false
        ]);

        return response()->json([
            'message' => 'The order has sent, you will receive response from admin with accepting or rejecting',
            'order' => $order
        ]);
    }    
}
