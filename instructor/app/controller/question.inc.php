<?php
session_start();
include_once 'autoloader.inc.php';

if (isset($_GET['uploadImage'])) {
  $up = uploadFile($_FILES['file']['tmp_name']);
  echo '../style/images/uploads/' . $up . '.jpg';
}elseif (isset($_GET['deleteImage'])) {
    deleteImage($_POST['src']);
    echo 'success';
}elseif (isset($_GET['deleteAnswer'])){
  $q = new question;
  if(is_numeric($_POST['ansID'])){
    $q->deleteAnswer($_POST['ansID']);
    echo 'success';
  }
}elseif (isset($_GET['addQuestion'])){
    $question = isset($_POST['questionText']) ? trim($_POST['questionText']) : null;
    $qtype = isset($_POST['qtype']) ? trim($_POST['qtype']) : null;
    $isTrue = isset($_POST['isTrue']) ? trim($_POST['isTrue']) : 0;
    $points = isset($_POST['points']) ? trim($_POST['points']) : 0;
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : 1;
    $course = $_POST['Course'];
    if($question == null){
      $_SESSION["error"][] = 'Question Can\' Be Empty';
      header('Location: ' . $_SERVER['HTTP_REFERER']);exit;
    }elseif($qtype == null){
      $_SESSION["error"][] = 'Question Type is not selected';
      header('Location: ' . $_SERVER['HTTP_REFERER']);exit;
    }

    $newQuestion = new question;
    $newQuestion->insertQuestion($question,$qtype,$course,$isTrue,$points,$difficulty);
    $_SESSION["info"][] = 'Question Successfully Added';
    if ($qtype == 0) {
        foreach ($_POST['MCQanswer'] as $key=>$qanswer) {
            $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
            $isCorrect = !empty($qanswer['isCorrect']) ? 1 : 0;
            if ($answer != null) {
                $newQuestion->insertAnswersToLast($answer, $isCorrect,null);
            }
        }
    } elseif ($qtype == 3) {
      foreach ($_POST['MSQanswer'] as $key=>$qanswer) {
          $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
          $isCorrect = !empty($qanswer['isCorrect']) ? 1 : 0;
          if ($answer != null) {
              $newQuestion->insertAnswersToLast($answer, $isCorrect,null);
          }
      }
    }elseif ($qtype == 2) {
        foreach ($_POST['Canswer'] as $key=>$canswer) {
            $answer = $canswer['answertext'];
            if ($answer != '') {
                $newQuestion->insertAnswersToLast($answer, 1, null);
            }
        }
    }elseif ($qtype == 4) {
        foreach ($_POST['match'] as $key=>$manswer) {
            $matchAnswer = $_POST['matchAnswer'][$key];
            $matchPoints = $_POST['matchPoints'][$key];
            $answer = $manswer;
            if ($manswer != '' and $matchAnswer != '') {
                $newQuestion->insertAnswersToLast($manswer, 1, $matchAnswer,$matchPoints);
            }
        }
    }
    header('Location: ../../?questions=add&topic=' . $course);exit;
} elseif (isset($_GET['deleteQuestion'])) {
    $qst = new question;
    $qst->setQuestionDelete($_GET['deleteQuestion']);
    header('Location: ../../?questions');
} elseif (isset($_GET['restoreQuestion'])) {
    $qst = new question;
    $qst->restoreQuestion($_GET['restoreQuestion']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} elseif (isset($_GET['PDeleteQuestion'])) {
    $qst = new question;
    $qst->pDeleteQuestion($_GET['PDeleteQuestion']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);

} elseif (isset($_GET['updateQuestion'])) {
    $id = isset($_POST['qid']) ? trim($_POST['qid']) : null;
    $question = isset($_POST['questionText']) ? trim($_POST['questionText']) : null;
    $qtype = isset($_POST['qtype']) ? trim($_POST['qtype']) : 0;
    $isTrue = isset($_POST['isTrue']) ? trim($_POST['isTrue']) : 0;
    $points = isset($_POST['points']) ? trim($_POST['points']) : 0;
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : 1;
    $course = $_POST['Course'];

    $newQuestion = new question;
    $newQuestion->updateQuestion($id,$question,$course,$points,$difficulty);
    $newQuestion->updateTF($id, $isTrue);

    if ($qtype == 0 || $qtype == 3) {
        foreach ($_POST['Qanswer'] as $key=>$qanswer) {
            $ansID = isset($qanswer['ansID']) ? trim($qanswer['ansID']) : null;
            $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
            $isCorrect = !empty($qanswer['isCorrect']) ? trim($qanswer['isCorrect']) : 0;
            if ($ansID == null) {
                if ($answer != null) {
                    $newQuestion->insertAnswers($id, $answer, $isCorrect);
                }
              } else {
                $newQuestion->updateAnswer($ansID, $answer, $isCorrect,null);
            }
        }
    } elseif ($qtype == 2) {
        foreach ($_POST['Canswer'] as $key=>$canswer) {
            $answer = $canswer['answertext'];
            if ($answer != '') {
                $newQuestion->insertAnswers($id,$answer,1);
            }
        }
    } elseif ($qtype == 4) {
      foreach ($_POST['match'] as $key=>$manswer) {
          $oldAns = isset($_POST['oldID'][$key]) ? $_POST['oldID'][$key] : null;
          $matchAnswer = $_POST['matchAnswer'][$key];
          $matchPoints = $_POST['matchPoints'][$key];
          if ($manswer != '' and $matchAnswer != '') {
            if($oldAns == null){
              $newQuestion->insertAnswers($id,$manswer,1,$matchAnswer,$matchPoints);
            }else{
              $newQuestion->updateAnswer($oldAns, $manswer, 1,$matchAnswer,$matchPoints);
            }
          }
      }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}elseif (isset($_GET['duplicateQuestion']) and is_numeric($_GET['duplicateQuestion'])){
        $id = $_GET['duplicateQuestion'];
        $q = new question;
        $q->duplicateQuestion($id);
        $newID = $q->getLastQuestion()->id;
        header('Location:../../?questions=view&id='. $newID);
}elseif (isset($_GET['export'])) {
      $q = new question;
      $course = $_POST['course'];

      $questions = $q->getByCourse($course);
      $crs = $questions[0]->course;
      $qr = [];
      $qTypes = [0=>'Multiple Choise',1=>'True/False',2=>'Complete',3=>'Multiple Select',4=>'Matching',5=>'Essay'];
      foreach($questions as $question){
        $id = $question->id;
        $quest = str_replace("&nbsp;", '', strip_tags($question->question));
        $type = $question->type;
        $difficulty = $question->difficulty;
        $typetext = $qTypes[$question->type];
        $points = $question->points;
        $isTrue = $question->isTrue;
        if($type == 0 || $type == 3){
          $answers = $q->getQuestionAnswers($id);
          $ans1 = (!empty($answers[0]->answer))?(($answers[0]->isCorrect)?('#!'. str_replace("&nbsp;", '', strip_tags($answers[0]->answer))):str_replace("&nbsp;", '', strip_tags($answers[0]->answer))):'';
          $ans2 = (!empty($answers[1]->answer))?(($answers[1]->isCorrect)?('#!'. str_replace("&nbsp;", '', strip_tags($answers[1]->answer))):str_replace("&nbsp;", '', strip_tags($answers[1]->answer))):'';
          $ans3 = (!empty($answers[2]->answer))?(($answers[2]->isCorrect)?('#!'. str_replace("&nbsp;", '', strip_tags($answers[2]->answer))):str_replace("&nbsp;", '', strip_tags($answers[2]->answer))):'';
          $ans4 = (!empty($answers[3]->answer))?(($answers[3]->isCorrect)?('#!'. str_replace("&nbsp;", '', strip_tags($answers[3]->answer))):str_replace("&nbsp;", '', strip_tags($answers[3]->answer))):'';
          array_push($qr,[$quest,$typetext,$points,$difficulty,$ans1,$ans2,$ans3,$ans4]);
        }elseif($type == 4){
          $answers = $q->getQuestionAnswers($id);
          $ans1 = (!empty($answers[0]->answer)?($answers[0]->answer . '>>'. $answers[0]->matchAnswer):'');
          $ans2 = (!empty($answers[1]->answer)?($answers[1]->answer . '>>'. $answers[1]->matchAnswer):'');
          $ans3 = (!empty($answers[2]->answer)?($answers[2]->answer . '>>'. $answers[2]->matchAnswer):'');
          $ans4 = (!empty($answers[3]->answer)?($answers[3]->answer . '>>'. $answers[3]->matchAnswer):'');
          array_push($qr,[$quest,$typetext,$points,$difficulty,$ans1,$ans2,$ans3,$ans4]);
        }elseif($type == 1){
          $answer = (($isTrue == 1)?'True':'False');
          array_push($qr,[$quest,$typetext,$points,$difficulty,$answer,'','','']);
        }elseif($type == 2){
          $answers = $q->getQuestionAnswers($id);
          $ans1 = (!empty($answers[0]->answer))?$answers[0]->answer:'';
          $ans2 = (!empty($answers[1]->answer))?$answers[1]->answer:'';
          $ans3 = (!empty($answers[2]->answer))?$answers[2]->answer:'';
          $ans4 = (!empty($answers[3]->answer))?$answers[3]->answer:'';
          array_push($qr,[$quest,$typetext,$points,$difficulty,$ans1,$ans2,$ans3,$ans4]);
        }elseif($type == 5){
          $answers = $q->getQuestionAnswers($id);
          array_push($qr,[$quest,$typetext,$points,$difficulty,'','','','']);
        }
      }
      $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load('../../questionsTemplate.xlsx');

      $baseRow = 14;
      foreach ($qr as $r => $dataRow) {
          $row = $baseRow + $r;
          $spreadsheet->getActiveSheet()->insertNewRowBefore($row, 1);

          $spreadsheet->getActiveSheet()->setCellValue('A' . $row, $dataRow[0])
              ->setCellValue('B' . $row, $dataRow[1])
              ->setCellValue('C' . $row, $dataRow[2])
              ->setCellValue('D' . $row, $dataRow[3])
              ->setCellValue('E' . $row, $dataRow[4])
              ->setCellValue('F' . $row, $dataRow[5])
              ->setCellValue('G' . $row, $dataRow[6])
              ->setCellValue('H' . $row, $dataRow[7]);
          $spreadsheet->getActiveSheet()->getStyle('B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
          $spreadsheet->getActiveSheet()->getStyle('C' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
          $spreadsheet->getActiveSheet()->getStyle('D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
      }
      $spreadsheet->setActiveSheetIndex(0);

      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="'. $crs .'_Questions.xlsx"');
      header('Cache-Control: max-age=0');
      header('Cache-Control: max-age=1');

      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Cache-Control: cache, must-revalidate');
      header('Pragma: public');

      $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
      $writer->save('php://output');
      exit;

}else{
  http_response_code(404);
}
