<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Library\Database\DatabaseManager;

/**
 * Kontroler odpowiadający kategorie.
 */
class CategoryController extends Controller {

    private $database = null;

    public function __construct(DatabaseManager $databaseManager) {
        $this->database = $databaseManager;
    }
    
    public function list() {
        $categories = $this->database->getCategories();

        // dodanie ścieżki do nazwy zdjęcia
        foreach ($categories as $category) {
            if (!is_null($category->image))
                $category->image = 'img/quiz/category' . $category->image;
        }

        return view('layouts.categories', ['categories' => $categories]);
    }
}
