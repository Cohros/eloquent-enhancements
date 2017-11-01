<?php

class PostAuthor extends AbstractModel
{
    protected $table = 'posts_authors';

    protected $primaryKey = 'id_post_author';

    protected $fillable = [
        'id_post',
        'id_user',
        'main',
    ];

    protected $validation_rules = [
        'id_post' => 'required|integer|exists:posts,id_post',
        'id_user' => 'required|integer|exists:users,id_user',
        'main' => 'required|in:0,1',
    ];

    public function post()
    {
        return $this->belongsTo('Post', 'id_post');
    }

    public function user()
    {
        return $this->belongsTo('User', 'id_user');
    }
}
