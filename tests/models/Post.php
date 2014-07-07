<?php

class Post extends BaseModel
{
    protected $table = 'posts';

    protected $fillable = [
        'title',
        'content',
    ];

    protected $validation_rules = [
        'title' => 'required',
        'content' => 'required',
    ];

    protected $relationshipsModels = [
        'authors' => 'PostAuthor',
    ];

    public function authors()
    {
        return $this->belongsToMany('User', 'posts_authors', 'post_id', 'user_id');
    }
}
