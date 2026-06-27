<?php

use App\Events\DeliveryImportStarted;
use App\Jobs\ProcessDeliveryImport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
    Event::fake();
});

function tenantWithUser(): array
{
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);

    return [$tenant, $user];
}

it('dispatches ProcessDeliveryImport job and DeliveryImportStarted event on valid CSV upload', function () {
    [$tenant, $user] = tenantWithUser();

    $csv = UploadedFile::fake()->createWithContent(
        'deliveries.csv',
        "tracking_number,status,recipient_name,recipient_phone,pickup_address,delivery_address\n" .
        "BD001AA,pending,John Doe,01711223344,Gulshan Dhaka,Banani Dhaka\n"
    );

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-Key' => $tenant->api_key])
        ->postJson('/api/v1/imports', ['file' => $csv]);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'message',
            'import_job_id',
            'status_url',
        ])
        ->assertJsonFragment(['message' => 'Import queued successfully.']);

    Queue::assertPushed(ProcessDeliveryImport::class, function ($job) use ($user) {
        return $job->importJob->user_id === $user->id;
    });

    Event::assertDispatched(DeliveryImportStarted::class);
});

it('rejects non-CSV files', function () {
    [$tenant, $user] = tenantWithUser();

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

    $this->actingAs($user)
        ->withHeaders(['X-Tenant-Key' => $tenant->api_key])
        ->postJson('/api/v1/imports', ['file' => $file])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

it('requires authentication', function () {
    $this->postJson('/api/v1/imports')
        ->assertStatus(401);
});
