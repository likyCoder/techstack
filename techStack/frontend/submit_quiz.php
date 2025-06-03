<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['quiz_id'])) {
    header("Location: classes.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = $_POST['quiz_id'];

// Record submission
$conn->query("INSERT INTO quiz_submissions (quiz_id, user_id) VALUES ($quiz_id, $user_id)");
$submission_id = $conn->insert_id;

// Get all questions for quiz
$questions = $conn->query("SELECT * FROM quiz_questions WHERE quiz_id = $quiz_id");

while ($question = $questions->fetch_assoc()) {
    $qid = $question['id'];
    $type = $question['question_type'];

    if ($type == 'multiple_choice') {
        $selected_option_id = $_POST["question_$qid"] ?? null;
        $conn->query("INSERT INTO quiz_answers (submission_id, question_id, selected_option_id) VALUES ($submission_id, $qid, " . ($selected_option_id ?: 'NULL') . ")");
    } else {
        $answer_text = $conn->real_escape_string($_POST["question_$qid"] ?? '');
        $conn->query("INSERT INTO quiz_answers (submission_id, question_id, answer_text) VALUES ($submission_id, $qid, '$answer_text')");
    }
}

// Redirect to results or confirmation
header("Location: quiz_results.php?submission_id=$submission_id");
exit();
?>
