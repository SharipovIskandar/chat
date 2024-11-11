<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    // Mass assignable atributlar
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
    ];

    /**
     * Xabar yuboruvchini olish.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Xabar qabul qiluvchini olish.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
