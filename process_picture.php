<?php
require 'config.php';

foreach ($_POST as $key => $value)  {
    $$key = $value;
}

foreach ($_GET as $key => $value)   {
    $$key = $value;
}

function UploadPhoto($person, &$message) {
    global $link;
    $target_dir = "pictures/";
    $filename = basename($_FILES["upload"]["name"]);
    $target_file = $target_dir . $filename;
    $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
    
    $uploadOk = true;
    if (file_exists($target_file)) {
        $message = "Sorry, file already exists.";
        $uploadOk = false;
    }
    // upload file
    if ($uploadOk == true) {
        if (move_uploaded_file($_FILES["upload"]["tmp_name"], $target_file)) {
            $message = "Photo " . $filename . ", has been uploaded";
            return true;
        } else {
            $message = "Sorry, there was an error uploading file.";
            return false;
        }
    }

}
    
switch ($process) {
    case "edit":
        if (!$_FILES['upload']['error']) {
            $sql1 = "SELECT name FROM picture WHERE ID = $proc_id";
            $result = mysqli_query($link, $sql1) or die(mysqli_error($link));
            $picture = mysqli_fetch_array($result);
            unlink("pictures/$picture[name]");
            
            if (UploadPhoto($person, $message)) {
                $filename = mysqli_escape_string($link, $_FILES['upload']['name']);
                $note = mysqli_escape_string($link, $note);
                $sql = "UPDATE picture SET person='$person', note='$note', name='$filename' WHERE ID = $proc_id";
            }
        } else {
            $sql = "UPDATE picture SET person='$person', note='$note' WHERE ID = $proc_id";
        }

        mysqli_query($link, $sql) or die(mysqli_error($link));
        $message = "Sucessfully edited picture $proc_id";
        break;

    case "delete":
        if ($DelSure) {
            $sql = "SELECT name FROM picture WHERE ID = $proc_id";
            $result = mysqli_query($link, $sql) or die(mysqli_error($link));
            $row = mysqli_fetch_array($result);
            $sql = "DELETE FROM picture WHERE ID = $proc_id";
            mysqli_query($link, $sql) or die(mysqli_error($link));

            if (unlink("pictures/$row[name]")) {
                $message = "Sucessfully deleted picture $proc_id";
            } else {
                $message = "Error deleting picture $proc_id";
            }
        } else {
            header("Location: delete_picture.php?proc_id=$proc_id");
        }
        break;

    case "add":
        if (UploadPhoto($person, $message)) {
            $filename = mysqli_escape_string($link, $_FILES['upload']['name']);
            $note = mysqli_escape_string($link, $note);
            $sql = "INSERT INTO picture (name, person, note) VALUES ('$filename', '$person', '$note')";
            mysqli_query($link, $sql) or die(mysqli_error($link));
        }
        break;
}

header("Location: control.php?message=$message");
// echo "<pre>";
// print_r(get_defined_vars());
// echo "</pre>";
?>