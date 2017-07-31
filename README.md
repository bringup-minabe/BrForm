# BrForm

フォームテンプレート

## 使い方

### fields.php
    <?php
        $fields = array(
            'name' => array(
                'display' => '氏名',
                'type' => 'text',
                'required' => true
            ),
            'name_kana' => array(
                'display' => 'ふりがな',
                'type' => 'text',
                'required' => true
            )
        );
    ?>

### Index
    
    <?php
        require_once('../includes/BrForm.php');
        require_once('../includes/fields.php');
        
        $bf = new BrForm();
        $bf->fields = $fields;
    ?>

    <input type="text" name="name" value="<?php echo $bf->out('name'); ?>" required="required">
    <input type="text" name="name_kana" value="<?php echo $bf->out('name_kana'); ?>" required="required">

### Check
    
    <?php
        require_once('../includes/BrForm.php');
        require_once('../includes/fields.php');
        
        $bf = new BrForm();
        $bf->fields = $fields;

        //set input session
        $bf->setInputSessoin();

        //check required
        $check = $bf->checkRequired();
        if ($check === false) {
            $bf->redirect('index.php?er=true');
            exit();
        }
    ?>

    <?php echo $bf->out('name'); ?>
    <?php echo $bf->out('name_kana'); ?>

### Send

    require_once('../includes/BrForm.php');
    require_once('../includes/fields.php');

    $bf = new BrForm();
    $bf->fields = $fields;

    //check required
    $check = $bf->checkRequired();
    if ($check === false) {
        $bf->redirect('index.php?er=true');
        exit();
    }

    //token check
    $token_check = $bf->checkToken();
    if ($token_check === false) {
        $bf->redirect('index.php');
        exit();
    }

    $send_error = false;
    
    $bf->To = 'Send To';
    $bf->From = 'From Email';
    $bf->FromName = 'From Name';
    $bf->Subject = '件名';
    $bf->Message = 'Send Message';

    try {
        $bf->sendEmail();
    } catch (Exception $e) {
        $send_error = true;
    }

    // CSVファイルへの書き込みをする場合
    // $bf->CsvFilePath = 'csv file path';
    // $bf->addCsvRow();

    session_destroy();