<?php

namespace HomebrewSpace\Controllers;

use HomebrewSpace\BaseController;
use HomebrewSpace\DatabaseManager;

class SubmissionController extends BaseController {
    protected $viewFolder = 'submission';

    protected $formInputNames = [
        'name' => 'Please provide a valid name.',
        'description' => 'Please provide a valid description.',
        'author' => 'Please provide a valid author name. (can be the name of a Group)',
        'rules-check' => 'You need to agree to the Rules & Guidelines.'
    ];

    private function IsFormSubmit() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function getCleanGithubRepoUrl($githubUrl) {
        $urlParts = parse_url($githubUrl);

        if (!isset($urlParts['host']) || !in_array($urlParts['host'], ['www.github.com', 'github.com'])) return false;

        $pathSegments = array_filter(explode('/', $urlParts['path']));
        if (count($pathSegments) < 2) {
            return false;
        }

        return 'https://github.com/' . $pathSegments[1] . '/' . $pathSegments[2];
    }

    /**
     * Submission form
     */
    public function formAction() {
        if ($this->IsFormSubmit()) {
            $errors = [];

            foreach($this->formInputNames as $formInputName => $formInputError) {
                if (!isset($_REQUEST[$formInputName]) || strlen($_REQUEST[$formInputName]) == 0) {
                    $errors[] = $formInputError;
                }
            }

            $cleanedGithubUrl = false;
            if (isset($_REQUEST['github']))
                $cleanedGithubUrl = $this->getCleanGithubRepoUrl($_REQUEST['github']);
            if ($cleanedGithubUrl === false)
                $errors[] = 'Github Url seems to be invalid. (valid example: https://github.com/OWNER/REPOSITORY/)';

            if (count($errors) > 0) {
                return ['formData' => $_POST, 'success' => false, 'errors' => $errors];
            }

            $stmt = DatabaseManager::Prepare('select * from app where github_url = :github_url');
            $stmt->bindValue('github_url', $cleanedGithubUrl);
            $stmt->execute();
            $app = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($app !== false) {
                return ['formData' => $_POST, 'success' => false, 'errors' => ['This app has already been submitted.']];
            }

            //@TODO: Check duplicates here already???
            $stmt = DatabaseManager::Prepare('INSERT INTO `app`(`name`, `description`, `author`, `github_url`, `state`, `created_at`, `updated_at`)' .
                                                ' VALUES (:name,:description,:author,:github_url,0,now(),now())');
            $stmt->bindValue('name', $_REQUEST['name']);
            $stmt->bindValue('description', $_REQUEST['description']);
            $stmt->bindValue('author', $_REQUEST['author']);
            $stmt->bindValue('github_url', $cleanedGithubUrl);
            $result = $stmt->execute();

            if ($result == false) {
                return ['formData' => $_POST, 'success' => false, 'errors' => ['There was an error while inserting your submission into the database.']];
            }

            return ['success' => true, 'message' => 'Your submission has been received, it will now be checked and accepted if it complies with our rules.'];
        }
    }
}
