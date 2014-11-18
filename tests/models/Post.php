<?php

class Post extends AbstractModel
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

    protected $relationshipsLimits = [
        'authors' => ':3',
    ];

    public function authors()
    {
        return $this->belongsToMany('User', 'posts_authors', 'post_id', 'user_id')->withTimestamps();
    }
}
