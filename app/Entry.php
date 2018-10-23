<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    public $children = [];

    protected $dates = [
        'started_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'duration' => 'integer',
        'stop' => 'boolean',
    ];

    public function prev()
    {
        return Entry::where('user_id', $this->user_id)
            ->where('started_at', '<', $this->started_at)
            ->orderBy('started_at', 'desc')
            ->first();
    }

    public function next()
    {
        return Entry::where('user_id', $this->user_id)
            ->where('started_at', '>', $this->started_at)
            ->orderBy('started_at', 'asc')
            ->first();
    }

    public function recalculateDuration()
    {
        $this->duration = 0;

        $next = $this->next();

        if (isset($next)) {
            $diff = $this->started_at->diff($next->started_at);

            $minutes = $diff->days * 24 * 60;
            $minutes += $diff->h * 60;
            $minutes += $diff->i;

            $this->duration = $minutes;
        }
    }

    public function timeDuration()
    {
        $hours = floor($this->duration / 60);
        $minutes = $this->duration - ($hours * 60);

        return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function task()
    {
        return $this->belongsTo('App\Task');
    }
}
