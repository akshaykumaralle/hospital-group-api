<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'parent_id',
    ];

    /**
     * Get the child groups for the current group.
     */
    public function children()
    {
        return $this->hasMany(Group::class, 'parent_id')->with('children');
    }

    /**
     * Get the parent group of the current group.
     */
    public function parent()
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }
}