<?php

class Product {
    public ?int $id = null;
    public string $name;
    public string $category;
    public float $price;
    public int $stock;

    public function __construct(string $name = '', string $category = '', float $price = 0, int $stock = 0) {
        $this->name = $name;
        $this->category = $category;
        $this->price = $price;
        $this->stock = $stock;
    }
}
