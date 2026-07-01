<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pizza;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['name' => 'admin'],
            ['role' => 'admin', 'password' => 'admin']);

        $pizzas = [
            ['name' => 'Margherita',    'price' => 9.50,  'description' => 'Tomate, mozzarella, basilic'],
            ['name' => 'Pepperoni',     'price' => 11.00, 'description' => 'Tomate, mozzarella, pepperoni'],
            ['name' => 'Quatre Fromages','price' => 12.50, 'description' => 'Mozzarella, gorgonzola, chèvre, parmesan'],
            ['name' => 'Végétarienne',  'price' => 10.50, 'description' => 'Tomate, mozzarella, poivrons, champignons, olives'],
            ['name' => 'Reine',         'price' => 11.50, 'description' => 'Tomate, mozzarella, jambon, champignons'],
            ['name' => 'Calzone',       'price' => 12.00, 'description' => 'Tomate, mozzarella, jambon, œuf (pliée)'],
        ];

        foreach ($pizzas as $pizza) {
            Pizza::updateOrCreate(['name' => $pizza['name']], $pizza);
        }
    }
}
