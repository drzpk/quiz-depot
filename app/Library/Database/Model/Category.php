<?php

namespace App\Library\Database\Model;

/**
 * Model kategorii.
 */
class Category extends BaseModel {
    /** Identyfikator kategorii */
    public $id = 0;
    /** Nazwa kategorii */
    public $name = "";
    /** Opis kategorii */
    public $description = "";
    /** Scieżka do zdjęcia kategorii */
    public $image = null;

    public function checkConstraints() {
        if (strlen($this->name) > 45) 
            return 'nazwa kategorii jest za długa';
        if (strlen($this->description) > 45)
            return 'opis kategorii jest za długi';
        if ($this->image != null && strlen($this->image) > 256)
            return 'ścieżka do zdjęcia kategorii jest za długa';

        return null;
    }
}