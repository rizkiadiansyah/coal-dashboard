<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * Nama koneksi database (terdaftar di config/database.php)
     *
     * @var string
     */
    protected $connection = 'mysql_cy';

    /**
     * Nama tabel yang digunakan di database mysql_cy
     *
     * @var string
     */
    protected $table = 'tbluser_dashboard';

    /**
     * Mengubah Primary Key bawaan dari 'id' menjadi 'username'
     *
     * @var string
     */
    protected $primaryKey = 'username';

    /**
     * Beritahu Laravel bahwa Primary Key tidak menggunakan Auto Increment (Integer)
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Tipe data dari Primary Key adalah String
     *
     * @var string
     */
    protected $keyType = 'string';

    public const ROLE_ADMIN = 1;
    public const ROLE_MANAGEMENT = 2;
    public const ROLE_MCR = 3;

    /**
     * Field-field yang diperbolehkan untuk diisi (Mass Assignable)
     * Ditambahkan field baru dari tabel tbluser_dashboard
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'email_backup',
        'password',
        'district',
        'active',
        'employee_id',
        'role',
    ];

    /**
     * Field yang disembunyikan saat serialisasi (misal ketika di-return sebagai JSON)
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Casting tipe data field
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Beritahu Filament/Laravel untuk menggunakan kolom 'username' 
     * sebagai identifier login utama (pengganti default email).
     * * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }
    
    /**
     * Mengatur hak akses siapa saja yang boleh login ke dalam Panel Filament.
     * Saat ini diset hanya user dengan status active = '1' yang bisa masuk.
     * * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->active === '1' && in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_MANAGEMENT,
            self::ROLE_MCR,
        ], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManagement(): bool
    {
        return $this->role === self::ROLE_MANAGEMENT;
    }

    public function isMcr(): bool
    {
        return $this->role === self::ROLE_MCR;
    }
}