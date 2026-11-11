<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterKaryawanRequest;
use App\Http\Requests\UpdateMasterKaryawanRequest;
use App\Http\Resources\MasterKaryawanResource;
use App\Models\MasterKaryawan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MasterKaryawanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        return MasterKaryawanResource::collection(MasterKaryawan::query()->with('user')->latest()->paginate($perPage));
    }

    public function store(StoreMasterKaryawanRequest $r): MasterKaryawanResource
    {
        $karyawan = DB::transaction(function () use ($r): MasterKaryawan {
            $data = $r->validated();
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            unset($data['email'], $data['password']);

            if ($r->hasFile('ktp_file')) {
                $data['ktp_path'] = $r->file('ktp_file')->store('karyawan/ktp', 'public');
            }

            if (filled($email) && filled($password)) {
                $data['user_id'] = $this->createLogin($data['nama_lengkap'], $email, $password)->id;
            }

            return MasterKaryawan::create($data);
        });

        return new MasterKaryawanResource($karyawan->load('user'));
    }

    public function show(MasterKaryawan $masterKaryawan): MasterKaryawanResource
    {
        return new MasterKaryawanResource($masterKaryawan->load('user'));
    }

    public function update(UpdateMasterKaryawanRequest $r, MasterKaryawan $masterKaryawan): MasterKaryawanResource
    {
        $previousKtpPath = $masterKaryawan->ktp_path;

        DB::transaction(function () use ($r, $masterKaryawan): void {
            $data = $r->validated();
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;
            unset($data['email'], $data['password']);

            if ($r->hasFile('ktp_file')) {
                $data['ktp_path'] = $r->file('ktp_file')->store('karyawan/ktp', 'public');
            }

            $user = $masterKaryawan->user;

            if ($user) {
                $userData = ['name' => $data['nama_lengkap']];

                if (filled($email)) {
                    $userData['email'] = $email;
                }

                if (filled($password)) {
                    $userData['password'] = $password;
                }

                $user->update($userData);
            } elseif (filled($email) && filled($password)) {
                $data['user_id'] = $this->createLogin($data['nama_lengkap'], $email, $password)->id;
            }

            $masterKaryawan->update($data);
        });

        if ($previousKtpPath !== null && $masterKaryawan->ktp_path !== $previousKtpPath) {
            Storage::disk('public')->delete($previousKtpPath);
        }

        return new MasterKaryawanResource($masterKaryawan->refresh()->load('user'));
    }

    public function destroy(MasterKaryawan $masterKaryawan): JsonResponse
    {
        $ktpPath = $masterKaryawan->ktp_path;
        $masterKaryawan->delete();
        if ($ktpPath !== null) {
            Storage::disk('public')->delete($ktpPath);
        }

        return response()->json(status: 204);
    }

    private function createLogin(string $name, string $email, string $password): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'teknisi'],
            ['display_name' => 'Teknisi', 'description' => 'Mobile View - Lapangan'],
        );

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role_id' => $role->id,
        ]);
    }
}
