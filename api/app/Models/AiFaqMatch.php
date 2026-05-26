<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiFaqMatch extends Model
{
    protected $table = 'ai_faq_matches';

    public $timestamps = true;

    protected $fillable = ['faq_id', 'chat_id', 'score'];
}
