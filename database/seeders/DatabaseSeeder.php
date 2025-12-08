<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Users (10)
        $users = [
            ['name' => 'Admin', 'email' => 'admin@gmail.com', 'role' => 'owner'],
            ['name' => 'Delivery Personnel', 'email' => 'delivery@aquastar.com', 'role' => 'delivery'],
            ['name' => 'Helper Personnel', 'email' => 'helper@aquastar.com', 'role' => 'helper'],
        ];

        $createdUsers = collect($users)->map(function ($user) {
            return User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'),
                'role' => $user['role'],
            ]);
        });

        $owner = $createdUsers->firstWhere('role', 'owner');
        $deliveryPerson = $createdUsers->firstWhere('role', 'delivery');

        // Customers (10)
        $customers = collect(range(1, 10))->map(function ($i) {
            return Customer::create([
                'name' => "Customer {$i}",
                'phone' => "0917".str_pad($i, 7, '0', STR_PAD_LEFT),
                'email' => "customer{$i}@example.com",
                'address' => "Sample Address {$i}",
                'is_regular' => $i % 2 === 0,
            ]);
        });

        // Inventory Items (10)
        $inventoryItems = [
            ['Gallon Containers', 'Empty gallon containers', 150, 30, 'container'],
            ['Caps', 'Bottle caps', 400, 50, 'cap'],
            ['Seals', 'Security seals', 350, 40, 'seal'],
            ['Stickers', 'Brand stickers', 500, 50, 'other'],
            ['Nozzles', 'Dispenser nozzles', 40, 10, 'other'],
            ['Hoses', 'Food-grade hoses', 25, 5, 'other'],
            ['Filters', 'Water filters', 30, 6, 'other'],
        ];

        foreach ($inventoryItems as [$name, $desc, $qty, $threshold, $type]) {
            InventoryItem::create([
                'name' => $name,
                'description' => $desc,
                'quantity' => $qty,
                'threshold' => $threshold,
                'type' => $type,
            ]);
        }

        // Orders (10)
        $baseDate = Carbon::now()->subDays(5);
        $customers->each(function (Customer $customer, $index) use ($owner, $deliveryPerson, $baseDate) {
            $isDelivery = $index % 2 === 0;
            $quantity = 1 + ($index % 5);
            $price = 30.00;
            $deliveryFee = $isDelivery ? 5.00 : 0;
            $total = ($quantity * $price) + ($quantity * $deliveryFee);

            Order::create([
                'customer_id' => $customer->id,
                'user_id' => $owner->id,
                'quantity' => $quantity,
                'water_price' => $price,
                'is_delivery' => $isDelivery,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $total,
                'payment_status' => $index % 3 === 0 ? 'unpaid' : 'paid',
                'payment_method' => $index % 2 === 0 ? 'gcash' : 'cash',
                'payment_reference' => $index % 2 === 0 ? 'GCASH-' . Str::random(8) : null,
                'order_status' => $index % 4 === 0 ? 'pending' : 'completed',
                'delivery_user_id' => $isDelivery ? $deliveryPerson?->id : null,
                'delivery_date' => $isDelivery ? $baseDate->copy()->addHours($index * 3) : null,
                'notes' => $isDelivery ? 'Delivery requested' : 'Pickup order',
            ]);
        });
    }
}