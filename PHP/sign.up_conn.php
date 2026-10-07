<?php
session_start(); 

include 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function generateNextIdNo($conn) {
    $currentYear = date('Y');
    $prefix = $currentYear . '-';
    $query = "SELECT idno FROM user WHERE idno LIKE '" . mysqli_real_escape_string($conn, $prefix) . "%'";
    $result = mysqli_query($conn, $query);
    $maxNum = 0;
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $parts = explode('-', $row['idno']);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $num = intval($parts[1]);
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
    }
    $nextNum = $maxNum + 1;
    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// Initialize variables to hold form data
$idno = $firstname = $middlename = $lastname = $suffix = $birthdate = $age = $sex = $country = $purokstreet = $brgy = $municipal = $province = $zipcode = $username = $email = "";
// Process the form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = isset($_POST["idno"]) ? trim($_POST["idno"]) : '';
    if (empty($idno) || !preg_match('/^\d{4}-\d{4}$/', $idno)) {
        $idno = generateNextIdNo($conn);
    } else {
        $exist_idno = mysqli_query($conn, "SELECT idno FROM user WHERE `idno` = '$idno'");
        if (mysqli_num_rows($exist_idno) > 0) {
            $idno = generateNextIdNo($conn);
        }
    }
    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $suffix = $_POST["suffix"];
    $birthdate = $_POST["birthdate"];
    $age = $_POST["age"];
    $sex = $_POST["sex"];
    $country = $_POST["country"];
    $purokstreet = $_POST["purokstreet"];
    $brgy = $_POST["brgy"];
    $municipal = $_POST["municipal"];
    $province = $_POST["province"];
    $zipcode = $_POST["zipcode"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Security Questions
    $security_question1 = isset($_POST["security_question1"]) ? mysqli_real_escape_string($conn, $_POST["security_question1"]) : '';
    $raw_ans1 = isset($_POST["security_answer1"]) ? strtolower(trim($_POST["security_answer1"])) : '';
    $security_answer1 = mysqli_real_escape_string($conn, password_hash($raw_ans1, PASSWORD_DEFAULT));

    $security_question2 = isset($_POST["security_question2"]) ? mysqli_real_escape_string($conn, $_POST["security_question2"]) : '';
    $raw_ans2 = isset($_POST["security_answer2"]) ? strtolower(trim($_POST["security_answer2"])) : '';
    $security_answer2 = mysqli_real_escape_string($conn, password_hash($raw_ans2, PASSWORD_DEFAULT));

    $security_question3 = isset($_POST["security_question3"]) ? mysqli_real_escape_string($conn, $_POST["security_question3"]) : '';
    $raw_ans3 = isset($_POST["security_answer3"]) ? strtolower(trim($_POST["security_answer3"])) : '';
    $security_answer3 = mysqli_real_escape_string($conn, password_hash($raw_ans3, PASSWORD_DEFAULT));

    // Validate allowed email domains (Gmail or University emails only)
    $email_clean = strtolower(trim($email));
    $email_domain = substr(strrchr($email_clean, "@"), 1);
    $is_gmail = ($email_domain === 'gmail.com');
    $is_university = (preg_match('/^[a-z0-9.-]+\.edu\.ph$/i', $email_domain) || preg_match('/^[a-z0-9.-]+\.edu$/i', $email_domain));
    if (!$is_gmail && !$is_university) {
        echo "<script>alert('Only Gmail (@gmail.com) and university email (@...edu.ph / @...edu) are allowed!'); location.href='registration.php';</script>";
        exit();
    }

    // Check for existing username, email, and password
    $exist_username = mysqli_query($conn, "SELECT * FROM user WHERE `username` = '$username'");
    $exist_email = mysqli_query($conn, "SELECT * FROM user WHERE `email` = '$email'");
    $exist_password = mysqli_query($conn, "SELECT * FROM user WHERE `password` = '$password'");

    if (mysqli_num_rows($exist_username) > 0) {
        // echo "<script>alert('Username already exists!');</script>";
        echo "<script>location.href='registration.php'</script>";
    } elseif (mysqli_num_rows($exist_email) > 0) {
        echo "<script>alert('Email already exists!');</script>";
        echo "<script>location.href='registration.php'</script>";
    } elseif (mysqli_num_rows($exist_password) > 0) {
        echo "<script>alert('Password already exists!');</script>";
        echo "<script>location.href='registration.php'</script>";
    } else {
        // If "Others" is selected, use customSuffix; otherwise, use extensionname
        $suffixToUse = ($suffix === "others") ? $_POST["customSuffix"] : $suffix;

        $sql = "INSERT INTO user (idno, firstname, middlename, lastname, suffix, birthdate, age, sex, email, username, country, purokstreet, brgy, municipal, province, password, zipcode, security_question1, security_answer1, security_question2, security_answer2, security_question3, security_answer3, status)   
                VALUES ('$idno', '$firstname', '$middlename', '$lastname', '$suffixToUse', '$birthdate', '$age', '$sex', '$email', '$username', '$country', '$purokstreet', '$brgy', '$municipal', '$province', '$password', '$zipcode', '$security_question1', '$security_answer1', '$security_question2', '$security_answer2', '$security_question3', '$security_answer3', 'active')";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            echo "<script>alert('Registration successful! You can now log in.');</script>";
            echo "<script>location.href='login.php'</script>";
        } else {
            die(mysqli_error($conn));
        }
    }
}
?>
