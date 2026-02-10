<?php

class Comment {
    public string $content = '';
    public ?int $id = null;
    public ?object $post = null;
    public int $postId = 0;
}
