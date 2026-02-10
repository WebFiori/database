<?php

/**
 * Domain entity - pure, no database knowledge.
 */
class Post {
    public ?Author $author = null;
    public int $authorId = 0;
    public array $comments = [];
    public ?int $id = null;
    public string $title = '';
}
