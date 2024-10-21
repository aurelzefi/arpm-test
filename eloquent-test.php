<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CartItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        // Point 1: Eager load relationships to prevent the n+1 problem
        $orders = Order::with('customer', 'items.product', 'cartItems')->get();
        $orderData = [];

        // This can be refactored to use the Collection
        foreach ($orders as $order) {
            $customer = $order->customer;
            // $items = $order->items;
            // $totalAmount = 0;
            // $itemsCount = 0;

            $itemsCount = $items->count();

            // foreach ($items as $item) {
            // $product = $item->product; // this is not used anywhere as far as I can see
            // $totalAmount += $item->price * $item->quantity; // this can be an accessor on the order model, used like $order->total
            // }

            $totalAmount = $order->total;

            // Point 3: This suggests that there is a relation between Order and CartItem, a BelongsTo from the CartItem and HasMany from the order's side, so we can just query the collection that we eager loaded
            // $lastAddedToCart = CartItem::where('order_id', $order->id)
            //     ->orderByDesc('created_at')
            //     ->first()
            //     ->created_at ?? null;

            $lastAddedToCart = $order
                ->cartItems
                ->where('order_id', $order->id)
                ->orderByDesc('created_at')
                ->first()
                ->created_at ?? null;

            // Point 2: Remove unnecessary query, access status in the order itself
            // $completedOrderExists = Order::where('id', $order->id)
            //     ->where('status', 'completed')
            //     ->exists();

            $orderData[] = [
                'order_id' => $order->id,
                'customer_name' => $customer->name,
                'total_amount' => $totalAmount,
                'items_count' => $itemsCount,
                'last_added_to_cart' => $lastAddedToCart,
                'completed_order_exists' => $order->status === 'completed', // check status on the order
                'created_at' => $order->created_at,
            ];
        }

        usort($orderData, function($a, $b) {
            // Point 4: Again, no need to query, we already have the orders and we can work with the collection
            // $aCompletedAt = Order::where('id', $a['order_id'])
            //     ->where('status', 'completed')
            //     ->orderByDesc('completed_at')
            //     ->first()
            //     ->completed_at ?? null;

            $aCompletedAt = $orders->where('id', $a['order_id'])
                ->where('status', 'completed')
                ->orderByDesc('completed_at')
                ->first()
                ->completed_at ?? null;

            // $bCompletedAt = Order::where('id', $b['order_id'])
            //     ->where('status', 'completed')
            //     ->orderByDesc('completed_at')
            //     ->first()
            //     ->completed_at ?? null;

            $bCompletedAt = $orders->where('id', $b['order_id'])
                ->where('status', 'completed')
                ->orderByDesc('completed_at')
                ->first()
                ->completed_at ?? null;

            return strtotime($bCompletedAt) - strtotime($aCompletedAt);
        });

        return view('orders.index', ['orders' => $orderData]);
    }
}

