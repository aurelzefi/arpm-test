<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

$employees = [
    ['name' => 'John', 'city' => 'Dallas'],
    ['name' => 'Jane', 'city' => 'Austin'],
    ['name' => 'Jake', 'city' => 'Dallas'],
    ['name' => 'Jill', 'city' => 'Dallas'],
];

$offices = [
    ['office' => 'Dallas HQ', 'city' => 'Dallas'],
    ['office' => 'Dallas South', 'city' => 'Dallas'],
    ['office' => 'Austin Branch', 'city' => 'Austin'],
];

// $output = [
//     "Dallas" => [
//         "Dallas HQ" => ["John", "Jake", "Jill"],
//         "Dallas South" => ["John", "Jake", "Jill"],
//     ],
//     "Austin" => [
//         "Austin Branch" => ["Jane"],
//     ],
// ];

$data = collect($offices)
    ->map(function (array $office) use ($employees) {
        // put the matching employees into the office array element
        $cityEmployees = collect($employees)
            ->filter(function ($employee) use ($office) {
                return Str::contains($office['office'], $employee['city']);
            })
            ->values()
            ->toArray();

        return array_merge($office, ['employees' => $cityEmployees]);
    })
    // group the offices by the city
    ->groupBy(function (array $office) {
        return explode(' ', $office['office'])[0];
    })
    // format the array elements
    ->map(function (Collection $office) {
        return $office
            ->mapWithKeys(
                fn (array $office) => [$office['office'] => collect($office['employees'])->pluck('name')->all()]
            )
            ->all();
    })
    ->all();

dd($data);
