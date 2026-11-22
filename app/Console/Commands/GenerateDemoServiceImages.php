<?php

namespace App\Console\Commands;

use Database\Seeders\DemoMarketplaceSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateDemoServiceImages extends Command
{
    protected $signature = 'servio:generate-demo-images
        {--images=2 : Number of images per listing, from 1 to 3}';

    protected $description = 'Generate demo service cover and gallery images using GPT Image 2.5 Flare';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Demo image generation is available in local and testing environments.');

            return self::FAILURE;
        }

        $count = filter_var($this->option('images'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
        $key = env('OPENAI_API_KEY');

        if ($count === false || ! is_string($key) || trim($key) === '') {
            $this->error('Set OPENAI_API_KEY and choose between 1 and 3 images per listing.');

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $angles = [
            'Wide view showing the service in progress.',
            'Close-up of the tools and the work being done.',
            'A different angle showing the finished result.',
        ];

        foreach (DemoMarketplaceSeeder::LISTINGS as $category => $listings) {
            foreach ($listings as [$title, $description]) {
                for ($position = 1; $position <= $count; $position++) {
                    $path = DemoMarketplaceSeeder::imagePath($category, $title, $position);

                    if ($disk->exists($path)) {
                        continue;
                    }

                    $this->line("Generating {$title} ({$position}/{$count})...");
                    $angle = $angles[$position - 1];

                    try {
                        $response = Http::withToken($key)->timeout(180)
                            ->post('https://api.openai.com/v1/images/generations', [
                                'model' => 'gpt-image-2.5-flare',
                                'prompt' => "Create a realistic editorial photograph for a local services marketplace in Madrid. Service: {$title}. {$description} {$angle} Natural lighting. No text, watermarks or branding.",
                                'size' => '1536x1024',
                                'quality' => 'medium',
                                'output_format' => 'webp',
                                'output_compression' => 82,
                            ]);
                    } catch (ConnectionException) {
                        $this->error('Could not connect to the OpenAI Image API. Run the command again to resume.');

                        return self::FAILURE;
                    }

                    if ($response->failed()) {
                        $this->error("OpenAI Image API returned HTTP {$response->status()}. Run the command again to resume after resolving the API error.");

                        return self::FAILURE;
                    }

                    $encoded = $response->json('data.0.b64_json');
                    $image = is_string($encoded) ? base64_decode($encoded, true) : false;

                    if ($image === false || (getimagesizefromstring($image)['mime'] ?? null) !== 'image/webp' || ! $disk->put($path, $image)) {
                        $this->error('The response did not contain a valid WebP image or the public disk could not save it.');

                        return self::FAILURE;
                    }
                }
            }
        }

        return $this->call('db:seed');
    }
}
