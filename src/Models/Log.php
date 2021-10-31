<?php

namespace MichaelBelgium\YoutubeAPI\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'youtube_logs';

    public $timestamps = ["created_at"];

    const UPDATED_AT = null;

    public function user()
    {
        if(config('auth.providers.users.model') !== null)
            return $this->belongsTo(config('auth.providers.users.model'));
        else
            throw new Exception('No user provider model defined.');
    }
}
