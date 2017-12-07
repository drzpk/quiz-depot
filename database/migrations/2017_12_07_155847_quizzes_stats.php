<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class QuizzesStats extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        $statsQuery = <<<EOT
        CREATE VIEW IF NOT EXISTS quizzes_stats AS (
            SELECT  *,
                    (
                        SELECT COUNT(*)
                        FROM questions AS s
                        WHERE s.quiz_id = q.quiz_id
                    ) AS question_count
            FROM quizzes AS q
        )
EOT;
        DB::statement($statsQuery);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::statement('DROP VIEW quizzes_stats');
    }
}
