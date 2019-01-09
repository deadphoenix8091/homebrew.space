<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;

class SubmissionController extends BaseController {
    protected $viewFolder = 'submission';

    protected $formInputNames = ['name', 'description', 'github', 'author', 'rules-check'];

    private function IsFormSubmit() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Submission form
     */
    public function formAction() {
        if ($this->IsFormSubmit()) {
            $errors = [];
            foreach($this->formInputNames as $formInputName) {
                if (!isset($_REQUEST[$formInputName])) {
                    $errors[] = $formInputName . ' is a required field and needs to be supplied.';
                }
            }

            if (count($errors) > 0) {
                return ['success' => false, 'errors' => $errors];
            }

            var_dump($_REQUEST);die;
        }
    }
}