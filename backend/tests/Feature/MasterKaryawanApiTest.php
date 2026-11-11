<?php

namespace Tests\Feature;

use App\Models\MasterKaryawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterKaryawanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_stores_employee_and_ktp_file(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $response = $this->post('/api/v1/master-karyawan', [
            'nama_lengkap' => 'Ayu Pratama',
            'nik' => 'EMP-001',
            'no_ktp' => '3174000000000001',
            'jabatan' => 'Operator',
            'no_telp' => '081234567890',
            'no_rekening' => '1234567890',
            'status' => 'Aktif',
            'ktp_file' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated()->assertJsonPath('data.nama_lengkap', 'Ayu Pratama');
        $employee = MasterKaryawan::query()->where('nik', 'EMP-001')->firstOrFail();
        $this->assertNotNull($employee->ktp_path);
        Storage::disk('public')->assertExists($employee->ktp_path);
    }
}
