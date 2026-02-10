<?php

/**
 * Domain entity - pure, no database knowledge.
 */
class Author {
    public ?int $id = null;
    public string $name = '';
    public array $posts = [];
}
