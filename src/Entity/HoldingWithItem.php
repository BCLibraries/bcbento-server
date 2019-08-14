<?php

namespace App\Entity;

use BCLib\PrimoClient\Holding;

class HoldingWithItem extends Holding
{
    /**
     * @var \BCLib\PrimoClient\Item[]
     */
    protected $items = [];

    /**
     * Holding constructor
     *
     * Build a holding with items from a parent holding
     *
     * @param Holding $parent
     */
    public function __construct(Holding $parent)
    {
        $parent_props = get_object_vars($parent);
        foreach ($parent_props AS $key => $value) {
            $this->$key = $value;
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param \BCLib\PrimoClient\Item[] $items
     * @return HoldingWithItem
     */
    public function setItems(array $items): HoldingWithItem
    {
        $this->items = $items;
        return $this;
    }

}