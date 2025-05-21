<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Exception;

class AppPivot extends Pivot
{
    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are UUIDs.
     *
     * @var bool
     */
    protected $keyIsUuid = true;

    /**
     * The UUID version to use.
     *
     * @var int
     */
    protected $uuidVersion = 4;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function($pivot) {
            $pivot->id = Uuid::uuid4()->toString();
        });

        static::created(function($pivot) {
        });

        static::saving(function($pivot) {
        });

        static::saved(function($pivot) {
        });

        static::updating(function($pivot) {
        });

        static::updated(function($pivot) {
        });
    }

    /**
     * @return string
     * @throws Exception
     * @throws Exception
     */
    protected function generateUuid(): string
    {
        switch ($this->uuidVersion) {
            case 1:
                return Uuid::uuid1()->toString();
            case 4:
                return Uuid::uuid4()->toString();
        }

        throw new Exception("UUID version [{$this->uuidVersion}] not supported.");
    }
}