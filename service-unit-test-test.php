<?php

namespace App\Services;

use App\Jobs\ProcessProductImage;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class SpreadsheetService
{
    public function processSpreadsheet($filePath)
    {
        $products_data = app('importer')->import($filePath);

        foreach ($products_data as $row) {
            $validator = Validator::make($row, [
                'product_code' => 'required|unique:products,code',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                continue;
            }

            $product = Product::create($validator->validated());

            ProcessProductImage::dispatch($product);
        }
    }
}

// Suppose this is the importer class
class Importer
{
    //
}

// it has been injected into the container in teh app service provider like so:

// public function boot()
// {
//     $this->app->bind('importer', function ($app) {
//         return new Importer();
//     });
// }

// extends Laravel test, to access the app to bind the mocked service
class SpreadServiceTest extends TestCase
{
    public function test_spreadsheet_can_be_processed_with_all_correct_items(): void
    {
        // We fake the bus dispatcher, so we don't really dispatch the job
        Bus::fake();

        $fakeData = [
            [
                'product_code' => $code1 = fake()->word(),
                'quantity' => $quantity1 = fake()->randomNumber(),
            ],
            [
                'product_code' => $code2 = fake()->word(),
                'quantity' => $quantity2 = fake()->randomNumber(),
            ],
        ];

        $mock = Mockery::mock(Importer::class);

        $mock->shouldReceive('import')->andReturn($fakeData);

        $this->container->instance(Importer::class, $mock);

        $this
            ->assertDatabaseCount(2)
            ->assertDatabaseHas(Product::class, [
                'product_code' => $code1,
                'quantity' => $quantity1,
            ])
            ->assertDatabaseHas(Product::class, [
                'product_code' => $code2,
                'quantity' => $quantity2,
            ]);

        // Both items are correct, so the job should have been dispatched twice
		Bus::assertDispatchedTimes(ProcessProductImage::class, 2);
	}

    public function test_spreadsheet_data_can_be_processed_with_wrong_items(): void
    {
        Bus::fake();

        $fakeData = [
            [
                'product_code' => $code1 = fake()->word(),
                'quantity' => $quantity1 = fake()->randomNumber(),
            ],
            // array element with non-correct data
            [
                'product_code' => null,
                'quantity' => null,
            ],
        ];

        $mock = Mockery::mock(Importer::class);

        $mock->shouldReceive('import')->andReturn($fakeData);

        $this->container->instance(Importer::class, $mock);

        // Assert the database has only one product
        $this
            ->assertDatabaseCount(1)
            ->assertDatabaseHas(Product::class, [
                'product_code' => $code1,
                'quantity' => $quantity1,
            ]);

        // Assert the job was dispatched only once
        Bus::assertNotDispatchedTimes(ProcessProductImage::class, 1);
    }
}
