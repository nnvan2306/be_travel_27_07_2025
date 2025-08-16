<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'markdown',
        'location',
        'thumbnail',
        'status',
        'tags',
        'slug'
    ];

    protected $casts = [
        'view_count' => 'integer',
    ];

    protected $appends = ['thumbnail_url'];

    /**
     * Boot method để tự động tạo slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });

        static::updating(function ($blog) {
            if ($blog->isDirty('title') && empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }

    /**
     * Accessor: Trả về URL ảnh thumbnail đầy đủ
     */
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }

    /**
     * Scope: Lọc theo status published
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Lọc theo status draft
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Tìm kiếm theo title hoặc description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        });
    }

    /**
     * Tăng số lượt xem (với bảo vệ chống spam)
     */
    public function incrementViewCount()
    {
        // Tăng view count và save ngay lập tức
        $this->increment('view_count');
        
        // Refresh model để có giá trị mới nhất
        $this->refresh();
        
        return $this;
    }
}
