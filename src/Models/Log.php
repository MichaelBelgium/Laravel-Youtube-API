<?php

namespace MichaelBelgium\YoutubeAPI\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'youtube_logs';

    public $timestamps = ["created_at"];

    const UPDATED_AT = null;
}
