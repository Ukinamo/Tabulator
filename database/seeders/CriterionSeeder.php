<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Criterion;
use Illuminate\Database\Seeder;

class CriterionSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'Talent Portion' => [
                ['name' => 'Stage Presence', 'max' => 30, 'sort' => 1],
                ['name' => 'Performance Quality', 'max' => 40, 'sort' => 2],
                ['name' => 'Audience Impact', 'max' => 30, 'sort' => 3],
            ],
            'Q&A Portion' => [
                ['name' => 'Relevance of Answer', 'max' => 40, 'sort' => 1],
                ['name' => 'Clarity & Delivery', 'max' => 30, 'sort' => 2],
                ['name' => 'Confidence', 'max' => 30, 'sort' => 3],
            ],
            'Attire & Runway' => [
                ['name' => 'Outfit Appropriateness', 'max' => 40, 'sort' => 1],
                ['name' => 'Poise & Grace', 'max' => 35, 'sort' => 2],
                ['name' => 'Overall Impression', 'max' => 25, 'sort' => 3],
            ],
        ];

        foreach ($map as $categoryName => $criteria) {
            /** @var \App\Models\Category|null $category */
            $category = Category::where('name', $categoryName)->first();

            if (! $category) {
                continue;
            }

            foreach ($criteria as $row) {
                Criterion::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $row['name'],
                    ],
                    [
                        'max_score' => $row['max'],
                        'description' => null,
                        'sort_order' => $row['sort'],
                    ],
                );
            }
        }
    }
}

