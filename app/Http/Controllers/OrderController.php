<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Traits\cartTrait;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

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
        if($order->status == 'In way' || $order->status == 'Delivered')
        return response()->json('Sorry, order can not be changed');
        $user = auth()->user();
        $data = request()->validate([
            'location' => 'required | string | min:3 | max:100'
        ]);

        $cart = $this->get_content_of_cart($user);

        if($cart['products'] == null)
            return response()->json('Sorry, you must add one product at least');

        $location = $data['location'];

        $order->update([
            'cart' => $cart,
            'location' => $location
        ]);
        
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
        if($order->status != 'Waiting for response')
        return response()->json('Soryy, order can not be deleted');
    
        $order->update([
            'status' => 'Deleted'
        ]);
        $order->delete();
    
        $noti = Notification::where('order_id', $order->id)->first();
        $noti->delete();
        
        return response()->json([
            'message' => 'Order deleted successfully',
            'order' => $order
        ]);
    }



    public function admin_response(Order $order)
    {
        if($order->status != 'Waiting for response')
            return response()->json(['error' => 'Order not found'], 404);

        if(request()->approval == 'true')
            {
                $order->update([
                    'status' => 'Period of editing'
                ]);
                
                return response()->json('Admin has approved your order, and you have 5 minutes to make changes to it');
            }
        else if(request()->approval == 'false')
            {
                auth()->user()->products()->detach();
                $order->update([
                    'status' => 'Rejected'
                ]);
                $order->delete();
                return response()->json('Admin has not approved your order, try again later');
            }
        else
            return response()->json('Invalid value');
    }



    public function period_of_editing_has_finished(Order $order)
    {
        $order->update([
            'status' => 'In way'
        ]);

        $user = auth()->user();
        $user->products->detach();

        return response()->json('Order in way');
    }

    public function delivered(Order $order)
    {
        $order->update([
            'status' => 'Delivered'
        ]);

        return response()->json('Order has delivered, thank you for your trust');
    }

    public function restore($order)
    {
        $order = Order::withTrashed()->find($order);
        $order->restore();
        $order->update([
            'status' => 'Waiting for response'
        ]);

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
