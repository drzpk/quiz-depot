<?php

namespace App\Library\Database\Model;

class Tag {
    public $id = 0;
    /** Nazwa tagu */
    public $name = "";

    public function checkConstraints() {
        if (strlen($this->name) > 32)
            return 'nazwa tagu jest za długa';

        return null;
    }
}