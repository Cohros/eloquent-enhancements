<?php

class PostAuthor extends BaseModel
{
    protected $table = 'posts_authors';

    protected $fillable = [
        'post_id',
        'user_id',
        'main',
    ];

    protected $validation_rules = [
        'post_id' => 'required|integer|exists:posts,id',
        'user_id' => 'required|integer|exists:users,id',
        'main' => 'required|in:0,1',
    ];

    public function post()
    {
        return $this->belongsTo('Post', 'post_id');
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
}
