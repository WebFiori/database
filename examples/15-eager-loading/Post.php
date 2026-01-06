<?php
/**
 * Domain entity - pure, no database knowledge.
 */
class Post {
    public ?int $id = null;
    public string $title = '';
    public int $authorId = 0;
    public ?Author $author = null;
    public array $comments = [];
}
