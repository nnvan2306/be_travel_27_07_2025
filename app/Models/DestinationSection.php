<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DestinationSection extends Model
{
    use HasFactory;

    protected $table = 'destination_sections';

    protected $fillable = [
        'destination_id',
        'type',
        'title',
        'content',
    ];

    protected $casts = [
        'content' => 'string', // tránh lỗi Laravel cast tự động
    ];

    /**
     * Các type cần parse JSON
     */
    protected $jsonTypes = ['highlight', 'gallery', 'regionalDelicacies'];

    /**
     * Quan hệ: section thuộc về một điểm đến
     */
    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id', 'destination_id');
    }

    /**
     * Accessor: parse content nếu là json
     */
    public function getContentAttribute($value)
    {
        if (in_array($this->attributes['type'] ?? '', $this->jsonTypes)) {
            return json_decode($value, true) ?? [];
        }

        return $value;
    }

    /**
     * Mutator: tự encode content nếu là json
     */
    public function setContentAttribute($value)
    {
        if (in_array($this->attributes['type'] ?? '', $this->jsonTypes) && is_array($value)) {
            $this->attributes['content'] = json_encode($value);
        } else {
            $this->attributes['content'] = $value;
        }
    }

}