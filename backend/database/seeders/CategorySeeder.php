<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Drink', 'icon' => 'utensils', 'color' => '#EF4444'],
            ['name' => 'Groceries', 'icon' => 'shopping-cart', 'color' => '#22C55E'],
            ['name' => 'Transportation', 'icon' => 'car', 'color' => '#3B82F6'],
            ['name' => 'Entertainment', 'icon' => 'film', 'color' => '#A855F7'],
            ['name' => 'Utilities', 'icon' => 'lightbulb', 'color' => '#F59E0B'],
            ['name' => 'Rent', 'icon' => 'home', 'color' => '#EC4899'],
            ['name' => 'Shopping', 'icon' => 'shopping-bag', 'color' => '#8B5CF6'],
            ['name' => 'Travel', 'icon' => 'plane', 'color' => '#06B6D4'],
            ['name' => 'Healthcare', 'icon' => 'heart-pulse', 'color' => '#10B981'],
            ['name' => 'Education', 'icon' => 'graduation-cap', 'color' => '#6366F1'],
            ['name' => 'Sports', 'icon' => 'dumbbell', 'color' => '#14B8A6'],
            ['name' => 'Gifts', 'icon' => 'gift', 'color' => '#F43F5E'],
            ['name' => 'Insurance', 'icon' => 'shield', 'color' => '#64748B'],
            ['name' => 'Taxes', 'icon' => 'file-text', 'color' => '#78716C'],
            ['name' => 'Other', 'icon' => 'circle-dot', 'color' => '#6B7280'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
