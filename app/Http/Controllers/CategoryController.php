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
    
    /**
     * Wyświetla listę kategorii.
     */
    public function list() {
        $categories = $this->database->getCategories();

        // dodanie ścieżki do nazwy zdjęcia
        foreach ($categories as $category) {
            if (!is_null($category->image))
                $category->image = 'img/quiz/category' . $category->image;
        }

        return view('layouts.categories', ['categories' => $categories]);
    }

    /**
     * Wyświetla wybraną kategorię.
     */
    public function displayCategory($categoryId, DatabaseManager $manager) {
        $category = $manager->getCategoryById((int) $categoryId);
        if ($category == null)
            return 'nie znaleziono kategorii';

        // usunięcie godziny z daty utworzenia quizu
        $quizzes = $manager->getQuizzes($category);
        foreach ($quizzes as &$quiz)
            $quiz->created = substr($quiz->created, 0, strpos($quiz->created, ' '));

        return view('layouts.category', [
            'category' => $category,
            'quizzes' => $quizzes
        ]);
    }
}
