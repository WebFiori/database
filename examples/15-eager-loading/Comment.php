<?php

class Comment {
    public ?int $id = null;
    public string $content = '';
    public int $postId = 0;
    public ?object $post = null;
}
