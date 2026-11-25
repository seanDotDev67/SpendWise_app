<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Supabase Storage client. The bucket must be created as "public" in the Supabase dashboard.
 */
class SupabaseStorage
{
    public static function configured(): bool
    {
        return config('services.supabase.url') && config('services.supabase.service_key');
    }

    public function upload(string $path, string $contents, string $mime): void
    {
        $response = Http::withToken(config('services.supabase.service_key'))
            ->withHeaders(['apikey' => config('services.supabase.service_key'), 'x-upsert' => 'true'])
            ->withBody($contents, $mime)
            ->post($this->objectUrl('object', $path));

        if ($response->failed()) {
            throw new RuntimeException('Supabase upload failed: '.$response->status().' '.$response->body());
        }
    }

    public function delete(string $path): bool
    {
        return Http::withToken(config('services.supabase.service_key'))
            ->withHeaders(['apikey' => config('services.supabase.service_key')])
            ->delete($this->objectUrl('object', $path))
            ->successful();
    }

    public function publicUrl(string $path): string
    {
        return $this->objectUrl('object/public', $path);
    }

    private function objectUrl(string $prefix, string $path): string
    {
        return config('services.supabase.url').'/storage/v1/'.$prefix.'/'.config('services.supabase.bucket').'/'.$path;
    }
}
